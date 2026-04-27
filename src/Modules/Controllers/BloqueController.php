<?php

namespace App\Modules\Controllers;

use App\Helpers\Controller;
use App\Helpers\Utils;
use App\Modules\Models\BloqueModel;
use App\Modules\Models\BloqueUsuarioModel;
use App\Modules\Models\NivelModel;
use App\Modules\Models\EjercicioModel;
use App\Modules\Models\SuscripcionModel;
use App\Modules\Models\RespuestaEjercicioModel;
use App\Modules\Models\RespuestaUsuarioModel;
use App\Modules\Models\ProgresoBloqueModel;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Controlador de Bloques Educativos
 */
class BloqueController extends Controller
{
    protected BloqueModel $bloqueModel;
    protected SuscripcionModel $suscripcionModel;
    protected EjercicioModel $ejercicioModel;
    protected RespuestaEjercicioModel $respuestaEjercicioModel;
    protected RespuestaUsuarioModel $respuestaUsuarioModel;
    protected ProgresoBloqueModel $progresoBloqueModel;

    protected BloqueUsuarioModel $bloqueUsuarioModel;
    protected NivelModel $nivelModel;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->bloqueModel = new BloqueModel();
        $this->suscripcionModel = new SuscripcionModel();
        $this->ejercicioModel = new EjercicioModel();
        $this->respuestaEjercicioModel = new RespuestaEjercicioModel();
        $this->respuestaUsuarioModel = new RespuestaUsuarioModel();
        $this->progresoBloqueModel = new ProgresoBloqueModel();
        $this->bloqueUsuarioModel = new BloqueUsuarioModel();
        $this->nivelModel = new NivelModel();
    }

    /**
     * Obtener ejercicios (preguntas y opciones) de un bloque o nivel específico
     * 
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function filtrarEjerciciosBloque(Request $request, Response $response, array $args): Response
    {
        $queryParams = $request->getQueryParams();
        $idBloque = $queryParams['idBloque'] ?? null;
        $idNivel = $queryParams['idNivel'] ?? null;

        if (!$idBloque && !$idNivel) {
            return Utils::responseJsonError($response, 'ID de bloque o nivel requerido', '', 400);
        }

        // --- VERIFICACIÓN DE ACCESO (Suscripción o Trial) ---
        $jwt = $request->getAttribute('jwt');
        if (!$jwt) {
            return Utils::responseJsonError($response, 'Usuario no autenticado', '', 401);
        }
        
        $usuarioId = (int)$jwt->data->id;
        $esAdmin = (bool)($jwt->data->esAdmin ?? false);

        // 1. Si es admin, pase directo
        if (!$esAdmin) {
            // 2. Revisar suscripción
            $tieneSuscripcion = $this->suscripcionModel->usuarioTieneSuscripcionActiva($usuarioId);

            if (!$tieneSuscripcion) {
                // 3. Revisar si tiene el bloque desbloqueado (Trial / Asignado)
                
                // Si solo tenemos idNivel, necesitamos saber el idBloque
                $bloqueIdVerificar = $idBloque;
                if (!$bloqueIdVerificar && $idNivel) {
                    $nivelRes = $this->nivelModel->find((int)$idNivel);
                    if ($nivelRes['status'] === 'ok' && !empty($nivelRes['data'])) {
                        $bloqueIdVerificar = $nivelRes['data']['idBloque'];
                    } else {
                        return Utils::responseJsonError($response, 'Nivel no encontrado', '', 404);
                    }
                }

                $tieneAccesoBloque = $this->bloqueUsuarioModel->usuarioTieneAccesoBloque($usuarioId, (int)$bloqueIdVerificar);

                if (!$tieneAccesoBloque) {
                    return Utils::responseJsonError($response, 'Acceso denegado: se requiere una suscripción activa', '', 403);
                }
            }
        }
        // ----------------------------------------------------

        $result = $this->ejercicioModel->findEjerciciosConOpciones(
            $idBloque ? (int)$idBloque : null,
            $idNivel ? (int)$idNivel : null
        );

        if ($result['status'] !== 'ok') {
            return Utils::responseJsonError($response, 'Error al obtener ejercicios');
        }

        $resultados = $result['data'];
        $ejerciciosMap = [];

        foreach ($resultados as $row) {
            $idEjercicio = $row['idEjercicio'];

            if (!isset($ejerciciosMap[$idEjercicio])) {
                $ejerciciosMap[$idEjercicio] = [
                    'idEjercicio' => (int)$row['idEjercicio'],
                    'pregunta' => $row['pregunta'],
                    'tipo' => $row['tipo'],
                    'opciones' => []
                ];
            }

            if ($row['idRespuesta']) {
                $ejerciciosMap[$idEjercicio]['opciones'][] = [
                    'idRespuesta' => (int)$row['idRespuesta'],
                    'texto' => $row['opcion'],
                    'esCorrecta' => (bool)$row['opcionEsCorrecta']
                ];
            }
        }

        return Utils::responseJsonOk($response, [
            'idBloque' => $idBloque ? (int)$idBloque : null,
            'idNivel' => $idNivel ? (int)$idNivel : null,
            'ejercicios' => array_values($ejerciciosMap)
        ]);
    }

    /**
     * Obtener bloques según estado de suscripción
     * 
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function obtenerPorSuscripcion(Request $request, Response $response, array $args): Response
    {
        // 1. Obtener datos del usuario desde el JWT (AuthMiddleware)
        $jwt = $request->getAttribute('jwt');
        if (!$jwt) {
            return Utils::responseJsonError($response, 'Usuario no autenticado', '', 401);
        }

        $usuarioId = (int)$jwt->data->id;
        $esAdmin = (bool)($jwt->data->esAdmin ?? false);

        // 2. Verificar suscripción activa
        $isSuscrito = $this->suscripcionModel->usuarioTieneSuscripcionActiva($usuarioId);

        // 3. Obtener bloques base según suscripcion/admin
        $result = $this->bloqueModel->findBloquesPorSuscripcion($usuarioId, $isSuscrito || $esAdmin);

        if ($result['status'] !== 'ok') {
            return Utils::responseJsonError($response, 'Error al obtener bloques');
        }

        $bloquesRaw = $result['data'];
        $bloques = [];

        // 4. Procesar cada bloque para niveles y progreso (Lógica del legacy)
        foreach ($bloquesRaw as $row) {
            $idBloque = (int) $row['idBloque'];

            // Contar niveles registrados para este bloque en la tabla 'nivel'
            // Podríamos hacerlo en el modelo, pero seguimos la lógica legacy por ahora
            $sqlNiveles = "SELECT COUNT(*) as totalNiveles FROM nivel WHERE idBloque = :idBloque";
            $nivelesRes = $this->bloqueModel->query($sqlNiveles, ['idBloque' => [$idBloque, \PDO::PARAM_INT]]);
            
            $totalNiveles = 0;
            if ($nivelesRes['status'] === 'ok' && !empty($nivelesRes['data'])) {
                $totalNiveles = (int) $nivelesRes['data'][0]['totalNiveles'];
            }

            if ($totalNiveles == 0) {
                $totalNiveles = 1; // Evitar división por cero
            }

            $porcentaje = (float) ($row['porcentajeCompletado'] ?? 0);
            $nivelesCompletados = round(($porcentaje / 100) * $totalNiveles);

            $row['totalTests'] = $totalNiveles;
            $row['completados'] = (int) $nivelesCompletados;
            $row['totalEjercicios'] = (int) ($row['totalEjercicios'] ?? 0);

            $bloques[] = $row;
        }

        // 5. Retornar en formato deseado
        return Utils::responseJsonOk($response, [
            'isSuscrito' => ($isSuscrito || $esAdmin),
            'bloques' => $bloques
        ]);
    }

    /**
     * Listar todos los bloques
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function listar(Request $request, Response $response, array $args): Response
    {
        $result = $this->bloqueModel->findAllWithNivel();

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, $result['data']);
        }

        return Utils::responseJsonError($response, 'Error al listar bloques');
    }

    /**
     * Ver detalle de un bloque
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function ver(Request $request, Response $response, array $args): Response
    {
        $bloqueId = (int)$args['id'];
        
        $result = $this->bloqueModel->findWithEjercicios($bloqueId);

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, $result['data']);
        }

        return Utils::responseJsonError($response, $result['error'] ?? 'Bloque no encontrado', '', 404);
    }

    /**
     * Crear un nuevo bloque
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function crear(Request $request, Response $response, array $args): Response
    {
        $params = $request->getParsedBody();
        
        $mensaje = Utils::requiredParams(['nombre'], $params);
        if ($mensaje != '') {
            return Utils::responseJsonError($response, $mensaje);
        }

        try {
            $pdo = \App\Helpers\Database::instance()->getConnection();
            $pdo->beginTransaction();
            
            // Crear el bloque usando PDO directamente
            $stmtBloque = $pdo->prepare(
                "INSERT INTO bloque (nombre, descripcion, finalidad) VALUES (:nombre, :descripcion, :finalidad)"
            );
            $stmtBloque->execute([
                ':nombre' => $params['nombre'],
                ':descripcion' => $params['descripcion'] ?? '',
                ':finalidad' => $params['finalidad'] ?? ''
            ]);
            
            $idBloque = (int)$pdo->lastInsertId();
            
            if ($idBloque <= 0) {
                $pdo->rollBack();
                return Utils::responseJsonError($response, 'Error al crear bloque');
            }
            
            // Crear automáticamente 3 niveles para el bloque
            $stmtNivel = $pdo->prepare("INSERT INTO nivel (idBloque, nivel) VALUES (:idBloque, :nivel)");
            for ($i = 1; $i <= 3; $i++) {
                $stmtNivel->execute([':idBloque' => $idBloque, ':nivel' => $i]);
            }
            
            $pdo->commit();
            
            return Utils::responseJsonOk($response, [
                'success' => true,
                'message' => 'Bloque creado exitosamente con 3 niveles',
                'idBloque' => $idBloque
            ], 201);
            
        } catch (\Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return Utils::responseJsonError($response, 'Error al crear bloque: ' . $e->getMessage());
        }
    }

    /**
     * Editar un bloque existente
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function editar(Request $request, Response $response, array $args): Response
    {
        $bloqueId = (int)$args['id'];
        $params = $request->getParsedBody();

        if (empty($params)) {
            return Utils::responseJsonError($response, 'No se enviaron datos para actualizar');
        }

        $result = $this->bloqueModel->update($bloqueId, $params);

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, 'Bloque actualizado exitosamente');
        }

        return Utils::responseJsonError($response, $result['error'] ?? 'Error al actualizar bloque');
    }

    /**
     * Eliminar un bloque
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function eliminar(Request $request, Response $response, array $args): Response
    {
        $bloqueId = (int)$args['id'];
        
        $result = $this->bloqueModel->delete($bloqueId);

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, 'Bloque eliminado exitosamente');
        }

        return Utils::responseJsonError($response, $result['error'] ?? 'Error al eliminar bloque');
    }

    /**
     * Obtener bloques por nivel
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function obtenerPorNivel(Request $request, Response $response, array $args): Response
    {
        $nivelId = (int)$args['nivelId'];
        
        $result = $this->bloqueModel->findByNivel($nivelId);

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, $result['data']);
        }

        return Utils::responseJsonError($response, 'Error al obtener bloques por nivel');
    }

    /**
     * Obtener bloques con progreso del usuario
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function obtenerConProgreso(Request $request, Response $response, array $args): Response
    {
        $params = $request->getQueryParams();
        $usuarioId = $params['usuarioId'] ?? $request->getAttribute('userId');
        
        if (!$usuarioId) {
             return Utils::responseJsonError($response, 'Usuario no identificado', '', 401);
        }

        $nivelId = isset($params['nivelId']) ? (int)$params['nivelId'] : null;

        $result = $this->bloqueModel->findWithProgreso((int)$usuarioId, $nivelId);

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, $result['data']);
        }

        return Utils::responseJsonError($response, 'Error al obtener progreso');
    }
    /**
     * Obtener contenido de todos los bloques con progreso del usuario
     * 
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function obtenerProgresoNivelesEjercicios(Request $request, Response $response, array $args): Response
    {
        $jwt = $request->getAttribute('jwt');
        if (!$jwt) {
            return Utils::responseJsonError($response, 'Usuario no autenticado', '', 401);
        }

        $usuarioId = (int)$jwt->data->id;

        $result = $this->bloqueModel->findProgresoNivelesEjercicios($usuarioId);

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, [
                'bloques' => $result['data']
            ]);
        }

        return Utils::responseJsonError($response, 'Error al obtener contenido de bloques');
    }

    /**
     * Obtener niveles (tests) de un bloque específico
     * 
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function getNiveles(Request $request, Response $response, array $args): Response
    {
        $idBloque = (int)$args['id'];

        if (!$idBloque) {
            return Utils::responseJsonError($response, 'ID de bloque requerido', '', 400);
        }

        $result = $this->bloqueModel->getNivelesByBloque($idBloque);

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, [
                'success' => true,
                'idBloque' => $idBloque,
                'niveles' => $result['data']
            ]);
        }

        return Utils::responseJsonError($response, $result['error'] ?? 'Error al obtener niveles del bloque');
    }
    /**
     * Corregir Test
     * Recibe: { respuestas: { idEjercicio: idRespuestaSeleccionada, ... } }
     * POST /bloques/corregir
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function corregirTest(Request $request, Response $response, array $args): Response
    {
        $jwt = $request->getAttribute('jwt');
        if (!$jwt) {
            return Utils::responseJsonError($response, 'Usuario no autenticado', '', 401);
        }

        $usuarioId = (int)$jwt->data->id;
        $params = $request->getParsedBody();
        $respuestasUsuario = $params['respuestas'] ?? [];

        if (empty($respuestasUsuario)) {
            return Utils::responseJsonError($response, 'No se enviaron respuestas', '', 400);
        }

        try {
            $aciertos = 0;
            $total = count($respuestasUsuario);
            $detalles = [];

            // Iniciar transacción
            $db = $this->bloqueModel->getConnection();
            $db->beginTransaction();

            $fechaNow = date('Y-m-d H:i:s');

            foreach ($respuestasUsuario as $idEjercicio => $idRespuesta) {
                // Verificar si la respuesta es correcta
                $esCorrecta = $this->respuestaEjercicioModel->esRespuestaCorrecta((int)$idRespuesta, (int)$idEjercicio);

                if ($esCorrecta) {
                    $aciertos++;
                }

                // Guardar en respuesta_usuario
                $this->respuestaUsuarioModel->guardarRespuesta(
                    $usuarioId,
                    (int)$idEjercicio,
                    (int)$idRespuesta,
                    $esCorrecta
                );

                $detalles[] = [
                    'idEjercicio' => (int)$idEjercicio,
                    'idRespuesta' => (int)$idRespuesta,
                    'esCorrecta' => $esCorrecta
                ];
            }

            // --- ACTUALIZACIÓN DE PROGRESO DEL BLOQUE ---
            // 1. Obtener idBloque del primer ejercicio
            $firstIdEjercicio = array_key_first($respuestasUsuario);
            $ejercicioRes = $this->ejercicioModel->find($firstIdEjercicio);
            
            if ($ejercicioRes['status'] === 'ok' && !empty($ejercicioRes['data'])) {
                $idBloque = (int)$ejercicioRes['data']['idBloque'];

                // 2. Contar total de ejercicios en el bloque
                $totalBloque = $this->ejercicioModel->countByBloque($idBloque);

                // 3. Contar ejercicios distintos ya respondidos por el usuario en este bloque
                $respondidos = $this->respuestaUsuarioModel->getRespondidosPorBloque($usuarioId, $idBloque);

                // 4. Calcular porcentaje
                $porcentaje = ($totalBloque > 0) ? min(100, round(($respondidos / $totalBloque) * 100, 2)) : 0;

                // 5. Actualizar o insertar en progreso_bloque
                $this->progresoBloqueModel->actualizarProgreso($usuarioId, $idBloque, $porcentaje);
            }

            $db->commit();

            return Utils::responseJsonOk($response, [
                'success' => true,
                'aciertos' => $aciertos,
                'total' => $total,
                'detalles' => $detalles
            ]);

        } catch (\Exception $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            return Utils::responseJsonError($response, 'Error al corregir el test: ' . $e->getMessage(), '', 500);
        }
    }
}
