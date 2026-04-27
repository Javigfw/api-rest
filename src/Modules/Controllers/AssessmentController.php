<?php

namespace App\Modules\Controllers;

use App\Helpers\Controller;
use App\Helpers\Utils;
use App\Helpers\Database;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;

/**
 * Controlador de Assessment
 * Maneja las preguntas de evaluación, cálculo de bloques recomendados y asignación
 */
class AssessmentController extends Controller
{
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
    }

    /**
     * GET /assessment/preguntas?lang={es|en|pt}
     * Obtiene preguntas del assessment con sus opciones
     */
    public function getPreguntas(Request $request, Response $response, array $args): Response
    {
        $lang = $request->getQueryParams()['lang'] ?? 'es';
        $validLangs = ['es', 'en', 'pt'];
        if (!in_array($lang, $validLangs)) {
            $lang = 'es';
        }

        try {
            $pdo = Database::getConnection();

            // FIX: Forzar utf8mb4 SOLO para esta consulta para soportar emojis
            // Esto no afecta a la configuración global de la base de datos
            $pdo->exec("SET NAMES utf8mb4");

            // Obtener preguntas activas
            $stmtPreguntas = $pdo->prepare("
                SELECT 
                    idPregunta as id,
                    orden,
                    texto_{$lang} as texto,
                    permite_multiple
                FROM assessment_pregunta 
                WHERE activo = 1 
                ORDER BY orden ASC
            ");
            $stmtPreguntas->execute();
            $preguntas = $stmtPreguntas->fetchAll(\PDO::FETCH_ASSOC);

            // Obtener opciones para cada pregunta
            $stmtOpciones = $pdo->prepare("
                SELECT 
                    idOpcion as id,
                    orden,
                    texto_{$lang} as texto,
                    icono
                FROM assessment_opcion 
                WHERE activo = 1 AND idPregunta = ?
                ORDER BY orden ASC
            ");

            $result = [];
            foreach ($preguntas as $p) {
                $stmtOpciones->execute([$p['id']]);
                $opciones = $stmtOpciones->fetchAll(\PDO::FETCH_ASSOC);

                $result[] = [
                    'id' => (int) $p['id'],
                    'orden' => (int) $p['orden'],
                    'texto' => $p['texto'],
                    'permite_multiple' => (bool) $p['permite_multiple'],
                    'opciones' => array_map(function ($o) {
                        return [
                            'id' => (int) $o['id'],
                            'orden' => (int) $o['orden'],
                            'texto' => $o['texto'],
                            'icono' => $o['icono']
                        ];
                    }, $opciones)
                ];
            }

            return Utils::responseJsonOk($response, [
                'lang' => $lang,
                'preguntas' => $result
            ]);

        } catch (\Exception $e) {
            return Utils::responseJsonError($response, 'Error al obtener preguntas', $e->getMessage(), 500);
        }
    }

    /**
     * POST /assessment/calcular
     * Body: { opciones: [1, 2, 9, 14, 17] }
     * Calcula bloques recomendados basándose en las opciones seleccionadas
     */
    public function calcular(Request $request, Response $response, array $args): Response
    {
        $body = $request->getParsedBody();
        $opciones = $body['opciones'] ?? [];

        if (empty($opciones) || !is_array($opciones)) {
            return Utils::responseJsonError($response, 'Se requiere un array de opciones', '', 400);
        }

        // Filtrar IDs válidos
        $opcionIds = array_filter(array_map('intval', $opciones), fn($id) => $id > 0);

        if (empty($opcionIds)) {
            return Utils::responseJsonError($response, 'No se recibieron IDs válidos', '', 400);
        }

        try {
            $pdo = Database::getConnection();

            // Calcular puntuación por bloque
            $placeholders = implode(',', array_fill(0, count($opcionIds), '?'));
            $sql = "
                SELECT 
                    b.idBloque as id,
                    b.nombre,
                    b.descripcion,
                    b.finalidad,
                    SUM(r.puntuacion) as puntos
                FROM assessment_regla r
                INNER JOIN bloque b ON r.idBloque = b.idBloque
                WHERE r.idOpcion IN ($placeholders)
                GROUP BY b.idBloque, b.nombre, b.descripcion, b.finalidad
                ORDER BY puntos DESC
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($opcionIds);
            $bloques = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Formatear y limitar a top 3
            $resultado = array_map(function ($b) {
                return [
                    'id' => (int) $b['id'],
                    'nombre' => $b['nombre'],
                    'descripcion' => $b['descripcion'],
                    'finalidad' => $b['finalidad'],
                    'puntos' => (int) $b['puntos']
                ];
            }, $bloques);

            $top3 = array_slice($resultado, 0, 3);

            return Utils::responseJsonOk($response, [
                'bloques' => $top3,
                'todos_bloques' => $resultado
            ]);

        } catch (\Exception $e) {
            return Utils::responseJsonError($response, 'Error al calcular bloques', $e->getMessage(), 500);
        }
    }

    /**
     * POST /assessment/asignar-bloques (requiere autenticación)
     * Body: { bloques: [1, 2, 3] }
     * Asigna bloques a un usuario autenticado
     */
    public function asignarBloques(Request $request, Response $response, array $args): Response
    {
        // Obtener usuario desde JWT
        $jwt = $request->getAttribute('jwt');
        if (!$jwt) {
            return Utils::responseJsonError($response, 'No autenticado', '', 401);
        }

        $userId = (int) $jwt->data->id;
        $body = $request->getParsedBody();
        $bloques = $body['bloques'] ?? [];

        if (!is_array($bloques) || empty($bloques)) {
            return Utils::responseJsonError($response, 'No se proporcionaron bloques', '', 400);
        }

        // Filtrar IDs válidos (1-12 típicamente)
        $bloques = array_filter(array_map('intval', $bloques), fn($id) => $id > 0 && $id <= 12);

        if (empty($bloques)) {
            return Utils::responseJsonError($response, 'Bloques inválidos', '', 400);
        }

        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            // Eliminar asignaciones previas del usuario
            $deleteStmt = $pdo->prepare("DELETE FROM bloque_usuario WHERE idUsuario = ?");
            $deleteStmt->execute([$userId]);

            // Insertar nuevas asignaciones
            // Tabla bloque_usuario tiene: idUsuario, idBloque, fechaDesbloqueo, fechaFin, razonDesbloqueo
            $insertStmt = $pdo->prepare("
                INSERT INTO bloque_usuario (idUsuario, idBloque, fechaDesbloqueo, fechaFin, razonDesbloqueo) 
                VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 1 YEAR), 'assessment')
            ");

            foreach ($bloques as $bloqueId) {
                $insertStmt->execute([$userId, $bloqueId]);
            }

            $pdo->commit();

            return Utils::responseJsonOk($response, [
                'message' => 'Bloques asignados correctamente',
                'bloques_asignados' => $bloques
            ]);

        } catch (\Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return Utils::responseJsonError($response, 'Error al asignar bloques', $e->getMessage(), 500);
        }
    }
}
