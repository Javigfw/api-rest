<?php

namespace App\Modules\Controllers;

use App\Helpers\Controller;
use App\Helpers\Utils;
use App\Helpers\Database;
use App\Modules\Models\EjercicioModel;
use App\Modules\Models\RespuestaEjercicioModel;
use App\Modules\Models\BloqueModel;
use App\Modules\Models\NivelModel;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Controlador de Ejercicios
 */
class EjercicioController extends Controller
{
    protected EjercicioModel $ejercicioModel;
    protected RespuestaEjercicioModel $respuestaModel;
    protected BloqueModel $bloqueModel;
    protected NivelModel $nivelModel;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->ejercicioModel = new EjercicioModel();
        $this->respuestaModel = new RespuestaEjercicioModel();
        $this->bloqueModel = new BloqueModel();
        $this->nivelModel = new NivelModel();
    }

    /**
     * Listar todos los ejercicios
     */
    public function listar(Request $request, Response $response, array $args): Response
    {
        $queryParams = $request->getQueryParams();
        $idBloque = $queryParams['idBloque'] ?? null;
        $idNivel = $queryParams['idNivel'] ?? null;
        
        // Si hay filtros de bloque o nivel, usar buscarActividades
        if ($idBloque || $idNivel) {
            $search = $queryParams['search'] ?? '';
            $result = $this->ejercicioModel->buscarActividades($search, $idBloque ? (int)$idBloque : null, $idNivel ? (int)$idNivel : null);
        } else {
            // Obtener todos ordenados por más recientes, con nombre de bloque
            $pdo = Database::instance()->getConnection();
            $sql = "SELECT e.idEjercicio, e.tipo, e.pregunta, e.idBloque, b.nombre as nombre_bloque
                    FROM ejercicio e
                    INNER JOIN bloque b ON e.idBloque = b.idBloque
                    ORDER BY e.idEjercicio DESC
                    LIMIT 50";
            $stmt = $pdo->query($sql);
            $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $result = ['status' => 'ok', 'data' => $data];
        }

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, $result['data']);
        }

        return Utils::responseJsonError($response, 'Error al listar ejercicios');
    }

    /**
     * Ver detalle de un ejercicio
     */
    public function ver(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $result = $this->ejercicioModel->findWithRespuestas($id);

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, $result['data']);
        }

        return Utils::responseJsonError($response, $result['error'] ?? 'Ejercicio no encontrado', '', 404);
    }

    /**
     * Crear un nuevo ejercicio
     */
    public function crear(Request $request, Response $response, array $args): Response {
        $params = $request->getParsedBody();
        
        // Validación
        $mensaje = Utils::requiredParams(['idBloque', 'tipo', 'pregunta'], $params);
        if ($mensaje != '') {
            return Utils::responseJsonError($response, $mensaje);
        }
        
        // Preparar datos
        $data = [
            'idBloque' => (int)$params['idBloque'],
            'idNivel' => !empty($params['idNivel']) ? (int)$params['idNivel'] : null,
            'tipo' => $params['tipo'],
            'pregunta' => $params['pregunta'],
            'respuestas' => $params['respuestas'] ?? []
        ];
        
        // Llamar al modelo
        $result = $this->ejercicioModel->createWithRespuestasAndNivel($data);
        
        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, [
                'success' => true,
                'message' => 'Ejercicio creado exitosamente',
                'idEjercicio' => $result['data']
            ], 201);
        }
        
        return Utils::responseJsonError($response, $result['error'] ?? 'Error al crear ejercicio');
    }

    /**
     * Editar un ejercicio
     */
    public function editar(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];
        $params = $request->getParsedBody();
        
        if (empty($params)) {
            return Utils::responseJsonError($response, 'No se enviaron datos para actualizar');
        }
        
        // Preparar datos para actualización
        $data = [];
        if (isset($params['tipo'])) $data['tipo'] = $params['tipo'];
        if (isset($params['pregunta'])) $data['pregunta'] = $params['pregunta'];
        if (isset($params['idBloque'])) $data['idBloque'] = (int)$params['idBloque'];
        if (isset($params['idNivel'])) $data['idNivel'] = (int)$params['idNivel'];
        if (isset($params['respuestas'])) $data['respuestas'] = $params['respuestas'];
        
        // Llamar al modelo
        $result = $this->ejercicioModel->updateWithRespuestasAndNivel($id, $data);
        
        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, [
                'success' => true,
                'message' => 'Ejercicio actualizado exitosamente'
            ]);
        }
        
        return Utils::responseJsonError($response, $result['error'] ?? 'Error al actualizar ejercicio');
    }

    /**
     * Eliminar un ejercicio
     */
    public function eliminar(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $result = $this->ejercicioModel->delete($id);

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, 'Ejercicio eliminado exitosamente');
        }

        return Utils::responseJsonError($response, $result['error'] ?? 'Error al eliminar ejercicio');
    }

    /**
     * Buscar ejercicios/actividades (Admin)
     */
    public function buscar(Request $request, Response $response, array $args): Response
    {
        $queryParams = $request->getQueryParams();
        $search = $queryParams['search'] ?? '';
        $idBloque = isset($queryParams['idBloque']) ? (int)$queryParams['idBloque'] : null;
        $idNivel = isset($queryParams['idNivel']) ? (int)$queryParams['idNivel'] : null;

        $result = $this->ejercicioModel->buscarActividades($search, $idBloque, $idNivel);

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, $result['data']);
        }

        return Utils::responseJsonError($response, 'Error al buscar actividades');
    }

    /**
     * Importación masiva de ejercicios por niveles
     * Usa nombre del bloque en lugar de ID y crea el bloque si no existe
     */
    public function importarBulk(Request $request, Response $response): Response {
        $params = $request->getParsedBody();

        // Detectar si es formato multi-bloque o single-bloque
        $bloquesAImportar = [];
        
        if (isset($params['bloques']) && is_array($params['bloques'])) {
            // Formato multi-bloque: {"bloques": [{"nombreBloque": "...", "niveles": {...}}, ...]}
            $bloquesAImportar = $params['bloques'];
        } elseif (isset($params['nombreBloque'])) {
            // Formato single-bloque (backward compatible): {"nombreBloque": "...", "niveles": {...}}
            $bloquesAImportar = [$params];
        } else {
            return Utils::responseJsonError($response, 'Formato inválido: debe contener "bloques" (array) o "nombreBloque"', '', 400);
        }

        if (empty($bloquesAImportar)) {
            return Utils::responseJsonError($response, 'No se encontraron bloques para importar', '', 400);
        }

        // Procesar cada bloque
        $resultados = [];
        $totalPreguntasImportadas = 0;
        $totalPreguntasEliminadas = 0;
        $erroresGlobales = [];

        foreach ($bloquesAImportar as $bloqueIndex => $bloqueData) {
            // Validación de entrada para cada bloque
            if (empty($bloqueData['nombreBloque']) || !is_string($bloqueData['nombreBloque'])) {
                $erroresGlobales[] = "Bloque #" . ($bloqueIndex + 1) . ": falta el campo nombreBloque o no es válido";
                continue;
            }

            if (!isset($bloqueData['niveles']) || !is_array($bloqueData['niveles'])) {
                $erroresGlobales[] = "Bloque '{$bloqueData['nombreBloque']}': falta el campo niveles o no es un objeto válido";
                continue;
            }

            $nombreBloque = trim($bloqueData['nombreBloque']);
            $niveles = $bloqueData['niveles'];
            
            // Procesar este bloque individual
            $resultadoBloque = $this->procesarBloqueIndividual(
                $nombreBloque, 
                $niveles,
                $bloqueData['descripcion'] ?? null,
                $bloqueData['finalidad'] ?? null
            );

            // Agregar resultado
            $resultados[] = array_merge($resultadoBloque, ['nombreBloque' => $nombreBloque]);
            
            if ($resultadoBloque['success']) {
                $totalPreguntasImportadas += $resultadoBloque['totalInsertadas'];
                $totalPreguntasEliminadas += $resultadoBloque['ejerciciosEliminados'];
            }
        }

        // Si todos los bloques fallaron
        if (count($resultados) === 0 && count($erroresGlobales) > 0) {
            return Utils::responseJsonError(
                $response,
                'No se pudo importar ningún bloque. Errores: ' . implode('; ', $erroresGlobales),
                '',
                400
            );
        }

        // Preparar respuesta
        $responseData = [
            'success' => true,
            'message' => 'Importación completada',
            'totalBloques' => count($resultados),
            'totalPreguntasImportadas' => $totalPreguntasImportadas,
            'totalPreguntasEliminadas' => $totalPreguntasEliminadas,
            'resultados' => $resultados
        ];

        if (count($erroresGlobales) > 0) {
            $responseData['advertencias'] = $erroresGlobales;
        }

        // Si solo hay un bloque, mantener formato de respuesta original para backward compatibility
        if (count($resultados) === 1) {
            $responseData = array_merge($responseData, $resultados[0]);
        }

        return Utils::responseJsonOk($response, $responseData);
    }

    /**
     * Procesar un bloque individual (lógica extraída del método original)
     * 
     * @param string $nombreBloque
     * @param array $niveles
     * @param string|null $descripcion
     * @param string|null $finalidad
     * @return array
     */
    private function procesarBloqueIndividual(string $nombreBloque, array $niveles, ?string $descripcion, ?string $finalidad): array
    {
        // 1. Buscar o crear bloque
        $bloqueResult = $this->bloqueModel->findByNombre($nombreBloque);
        
        $bloqueExistente = false;
        $idBloque = null;
        $ejerciciosEliminados = 0;
        
        if ($bloqueResult['status'] === 'ok') {
            // Bloque existe
            $bloqueExistente = true;
            $idBloque = (int)$bloqueResult['data']['idBloque'];
            
            // Eliminar todo el contenido existente
            $deleteResult = $this->ejercicioModel->deleteByBloqueWithRelations($idBloque);
            
            if ($deleteResult['status'] !== 'ok') {
                return [
                    'success' => false,
                    'error' => 'Error al eliminar ejercicios existentes: ' . ($deleteResult['error'] ?? '')
                ];
            }
            
            $ejerciciosEliminados = $deleteResult['ejerciciosEliminados'];
        } else {
            // Bloque no existe, crearlo
            $createBloqueResult = $this->bloqueModel->createWithData([
                'nombre' => $nombreBloque,
                'descripcion' => $descripcion,
                'finalidad' => $finalidad
            ]);
            
            if ($createBloqueResult['status'] !== 'ok') {
                return [
                    'success' => false,
                    'error' => 'Error al crear bloque: ' . ($createBloqueResult['error'] ?? '')
                ];
            }
            
            $idBloque = (int)$createBloqueResult['data'];
        }

        // 2. Procesar niveles y ejercicios
        $totalInsertadas = 0;
        $errores = [];
        
        foreach ($niveles as $numNivel => $preguntas) {
            if (!is_array($preguntas) || empty($preguntas)) {
                continue;
            }

            $numNivel = (int)$numNivel;

            // Buscar o crear nivel
            $nivelResult = $this->nivelModel->findOrCreateByBloqueAndNumero($idBloque, $numNivel);
            
            if ($nivelResult['status'] !== 'ok') {
                $errores[] = "Error al crear/buscar nivel $numNivel";
                continue;
            }
            
            $idNivel = $nivelResult['data'];

            // Procesar cada pregunta del nivel
            foreach ($preguntas as $index => $pregunta) {
                // Validaciones
                if (empty($pregunta['tipo']) || empty($pregunta['pregunta']) || empty($pregunta['respuestas'])) {
                    $errores[] = "Nivel $numNivel, pregunta " . ($index + 1) . ": datos incompletos";
                    continue;
                }

                if (!is_array($pregunta['respuestas']) || count($pregunta['respuestas']) < 2) {
                    $errores[] = "Nivel $numNivel, pregunta " . ($index + 1) . ": debe tener al menos 2 respuestas";
                    continue;
                }

                // Verificar que haya al menos una respuesta correcta
                $tieneCorrecta = false;
                foreach ($pregunta['respuestas'] as $resp) {
                    if (isset($resp['esCorrecta']) && $resp['esCorrecta'] === true) {
                        $tieneCorrecta = true;
                        break;
                    }
                }

                if (!$tieneCorrecta) {
                    $errores[] = "Nivel $numNivel, pregunta " . ($index + 1) . ": sin respuesta correcta";
                    continue;
                }

                // Crear ejercicio completo usando el modelo
                $ejercicioData = [
                    'idBloque' => $idBloque,
                    'idNivel' => $idNivel,
                    'tipo' => $pregunta['tipo'],
                    'pregunta' => $pregunta['pregunta'],
                    'respuestas' => $pregunta['respuestas']
                ];

                $createResult = $this->ejercicioModel->createWithRespuestasAndNivel($ejercicioData);

                if ($createResult['status'] === 'ok') {
                    $totalInsertadas++;
                } else {
                    $errores[] = "Nivel $numNivel, pregunta " . ($index + 1) . ": " . ($createResult['error'] ?? 'Error desconocido');
                }
            }
        }

        // 3. Preparar respuesta individual del bloque
        $resultado = [
            'success' => true,
            'idBloque' => $idBloque,
            'bloqueExistente' => $bloqueExistente,
            'ejerciciosEliminados' => $ejerciciosEliminados,
            'totalInsertadas' => $totalInsertadas
        ];

        if (count($errores) > 0) {
            $resultado['advertencias'] = array_slice($errores, 0, 10);
            $resultado['totalAdvertencias'] = count($errores);
        }

        return $resultado;
    }
}

