<?php

namespace App\Modules\Controllers;

use App\Helpers\Controller;
use App\Helpers\Utils;
use App\Helpers\Database;
use App\Modules\Models\EjercicioModel;
use App\Modules\Models\RespuestaEjercicioModel;
use App\Modules\Models\RespuestaUsuarioModel;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Controlador para la funcionalidad Trial/Desafío
 */
class TrialController extends Controller
{
    protected EjercicioModel $ejercicioModel;
    protected RespuestaEjercicioModel $respuestaEjercicioModel;
    protected RespuestaUsuarioModel $respuestaUsuarioModel;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->ejercicioModel = new EjercicioModel();
        $this->respuestaEjercicioModel = new RespuestaEjercicioModel();
        $this->respuestaUsuarioModel = new RespuestaUsuarioModel();
    }

    /**
     * Obtener preguntas aleatorias de un bloque
     * POST /trial/questions
     * Body: { idBloque: int }
     */
    public function getQuestions(Request $request, Response $response, array $args): Response
    {
        $params = $request->getParsedBody();
        $blockId = $params['idBloque'] ?? $params['blockId'] ?? null;

        if (!is_numeric($blockId)) {
            return Utils::responseJsonError($response, 'idBloque requerido', '', 400);
        }

        $blockId = (int) $blockId;
        $limit = 3;

        // 1. Obtener ejercicios del bloque (todos)
        $ejerciciosResult = $this->ejercicioModel->findByBloque($blockId);

        if ($ejerciciosResult['status'] !== 'ok' || empty($ejerciciosResult['data'])) {
            // Formato legado: devuelve 200 con array vacío si no hay preguntas
            return Utils::responseJsonOk($response, [
                'blockId' => $blockId,
                'questions' => []
            ]);
        }

        $ejercicios = $ejerciciosResult['data'];

        // Aleatorizar y limitar
        shuffle($ejercicios);
        $ejercicios = array_slice($ejercicios, 0, $limit);

        $questions = [];

        foreach ($ejercicios as $ej) {
            // 2. Obtener respuestas para cada ejercicio
            $respuestasResult = $this->respuestaEjercicioModel->findByEjercicio($ej['idEjercicio'], true); // true para tener esCorrecta y armar índices
            $respuestas = $respuestasResult['data'] ?? [];

            $opts = [];

            foreach ($respuestas as $r) {
                $opts[] = $r['texto'];
            }

            $questions[] = [
                'id' => (int) $ej['idEjercicio'],
                'question' => $ej['pregunta'],
                'options' => $opts,
                'explanation' => ''
            ];
        }

        return Utils::responseJsonOk($response, [
            'blockId' => $blockId,
            'questions' => $questions
        ]);
    }

    /**
     * Verificar respuesta
     * POST /trial/check
     * Body: { idEjercicio: int, respuesta: string }
     */
    public function checkAnswer(Request $request, Response $response, array $args): Response
    {
        $params = $request->getParsedBody();
        $idEjercicio = $params['idEjercicio'] ?? null;
        $respuestaTexto = $params['respuesta'] ?? null;

        if (!$idEjercicio || $respuestaTexto === null) {
            return Utils::responseJsonError($response, 'Faltan datos (idEjercicio, respuesta)', '', 400);
        }

        // --- 1. INTENTAR OBTENER USUARIO (JWT) ---
        // La ruta es pública, así que decodificamos manualmente si viene el header
        $userId = null;
        $authHeader = $request->getHeaderLine('Authorization');
        if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
            try {
                $parts = explode('.', $token);
                if (count($parts) === 3) {
                    // Base64Url Decode (reemplazar -_ por +/)
                    $payloadJson = base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1]));
                    $payload = json_decode($payloadJson, true);
                    if (isset($payload['data']['id'])) {
                        $userId = $payload['data']['id'];
                    }
                }
            } catch (\Exception $e) {
                // Token inválido, ignorar usuario (modo anónimo)
            }
        }
        // -----------------------------------------

        // Buscar si esta respuesta existe para este ejercicio y si es correcta
        $todasRespuestas = $this->respuestaEjercicioModel->findByEjercicio((int) $idEjercicio, true);

        if ($todasRespuestas['status'] !== 'ok') {
            return Utils::responseJsonError($response, 'Error al verificar respuesta');
        }

        $respuestas = $todasRespuestas['data'];
        $isCorrect = false;
        $correctText = null;
        $selectedAnswerId = null;

        // Normalizar respuesta del usuario: trim y lowercase para comparación
        $respuestaNormalizada = trim(strtolower($respuestaTexto));

        foreach ($respuestas as $r) {
            // Guardar el texto correcto por si falla (texto original, sin normalizar)
            if ((int) $r['esCorrecta'] === 1) {
                $correctText = $r['texto'];
            }

            // Normalizar texto de la opción para comparación
            $textoNormalizado = trim(strtolower($r['texto']));

            // Verificar coincidencia de texto (case-insensitive, sin espacios extra)
            if ($textoNormalizado === $respuestaNormalizada) {
                $selectedAnswerId = (int) $r['idRespuesta'];
                if ((int) $r['esCorrecta'] === 1) {
                    $isCorrect = true;
                }
            }
        }

        // --- GUARDAR ESTADÍSTICA (Si hay usuario y respuesta válida) ---
        if ($userId && $selectedAnswerId) {
            try {
                $this->respuestaUsuarioModel->guardarRespuesta(
                    (int) $userId,
                    (int) $idEjercicio,
                    (int) $selectedAnswerId,
                    $isCorrect
                );
            } catch (\Exception $e) {
                // Silenciar error de stats
            }
        }
        // -------------------------------------------------------------

        return Utils::responseJsonOk($response, [
            'correct' => $isCorrect,
            'correctAnswer' => $correctText,
            'message' => $isCorrect ? '¡Correcto!' : 'Respuesta incorrecta'
        ]);
    }

    /**
     * Sincronizar respuestas almacenadas localmente tras registro/login
     * POST /trial/sync
     * Body: { answers: [ {idEjercicio, respuesta, esCorrecto, fecha}, ... ] }
     */
    public function syncAnswers(Request $request, Response $response, array $args): Response
    {
        // 1. Obtener usuario autenticado (Middleware Auth requerido)
        $jwt = $request->getAttribute('jwt');
        if (!$jwt || !isset($jwt->data->id)) {
            return Utils::responseJsonError($response, 'Usuario no autenticado', '', 401);
        }
        $userId = $jwt->data->id;

        $params = $request->getParsedBody();
        $answers = $params['answers'] ?? [];

        if (empty($answers) || !is_array($answers)) {
            return Utils::responseJsonOk($response, ['message' => 'No hay respuestas para sincronizar']);
        }

        try {
            $count = 0;

            // Iterar respuestas recibidas del frontend
            foreach ($answers as $ans) {
                if (!isset($ans['idEjercicio']) || !isset($ans['respuesta']))
                    continue;

                $ejercicioId = (int) $ans['idEjercicio'];
                $respuestaTexto = trim(strtolower($ans['respuesta']));
                $fecha = $ans['fecha'] ?? date('Y-m-d H:i:s');
                $isCorrect = !empty($ans['esCorrecto']);

                // Buscar el idRespuesta correspondiente al texto
                $opcionesResult = $this->respuestaEjercicioModel->findByEjercicio($ejercicioId, true);
                $opciones = $opcionesResult['data'] ?? [];

                $idRespuestaEncontrada = null;

                foreach ($opciones as $opcion) {
                    if (trim(strtolower($opcion['texto'])) === $respuestaTexto) {
                        $idRespuestaEncontrada = (int) $opcion['idRespuesta'];
                        // Validar que la corrección coincida (doble check)
                        // if ((int)$opcion['esCorrecta'] === 1) $isCorrect = true;
                        break; // Encontrado
                    }
                }

                if ($idRespuestaEncontrada) {
                    // Usar modelo para guardar
                    // Nota: RespuestaUsuarioModel::guardarRespuesta usa date('Y-m-d H:i:s') internamente, 
                    // si queremos respetar la fecha original tendríamos que modificar el modelo o pasarla si lo soportase.
                    // El modelo actual NO recibe fecha por parámetro en guardarRespuesta, usa NOW().
                    // Para respetar la fecha offline, haré una inserción manual usando el modelo como base o extenderé el modelo.
                    // Por simplicidad y respeto al modelo existente, usaremos guardarRespuesta (fecha será la de sync).

                    // Si es crítico la fecha histórica, deberíamos modificar RespuestaUsuarioModel->guardarRespuesta para aceptar fecha opcional.
                    // Asumiremos fecha de sincronización es aceptable, o modificamos la inserción aquí.
                    // Dado el constraint del usuario de usar la tabla existente, mejor usar el método existente.

                    $this->respuestaUsuarioModel->guardarRespuesta(
                        $userId,
                        $ejercicioId,
                        $idRespuestaEncontrada,
                        $isCorrect
                    );
                    $count++;
                }
            }

            return Utils::responseJsonOk($response, [
                'message' => "Sincronizadas $count respuestas",
                'synced_count' => $count
            ]);

        } catch (\Exception $e) {
            return Utils::responseJsonError($response, 'Error sincronizando respuestas', $e->getMessage(), 500);
        }
    }
}
