<?php

namespace App\Modules\Controllers;

use App\Helpers\Controller;
use App\Helpers\Utils;
use App\Modules\Models\RespuestaEjercicioModel;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Controlador de Respuestas de Ejercicios
 */
class RespuestaEjercicioController extends Controller
{
    protected RespuestaEjercicioModel $respuestaEjercicioModel;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->respuestaEjercicioModel = new RespuestaEjercicioModel();
    }

    /**
     * Listar todas las respuestas
     */
    public function listar(Request $request, Response $response, array $args): Response
    {
        $queryParams = $request->getQueryParams();
        $idEjercicio = $queryParams['idEjercicio'] ?? null;
        
        if ($idEjercicio) {
            $result = $this->respuestaEjercicioModel->findByEjercicio((int)$idEjercicio, true);
        } else {
            $result = $this->respuestaEjercicioModel->findAll();
        }

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, $result['data']);
        }

        return Utils::responseJsonError($response, 'Error al listar respuestas');
    }

    /**
     * Ver detalle de una respuesta
     */
    public function ver(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $result = $this->respuestaEjercicioModel->find($id);

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, $result['data']);
        }

        return Utils::responseJsonError($response, $result['error'] ?? 'Respuesta no encontrada', '', 404);
    }

    /**
     * Crear una nueva respuesta
     */
    public function crear(Request $request, Response $response, array $args): Response
    {
        $params = $request->getParsedBody();
        
        $mensaje = Utils::requiredParams(['idEjercicio', 'texto', 'esCorrecta'], $params);
        if ($mensaje != '') {
            return Utils::responseJsonError($response, $mensaje);
        }

        $result = $this->respuestaEjercicioModel->create($params);

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, 'Respuesta creada exitosamente', 201);
        }

        return Utils::responseJsonError($response, $result['error'] ?? 'Error al crear respuesta');
    }

    /**
     * Editar una respuesta
     */
    public function editar(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $params = $request->getParsedBody();

        if (empty($params)) {
            return Utils::responseJsonError($response, 'No se enviaron datos para actualizar');
        }

        $result = $this->respuestaEjercicioModel->update($id, $params);

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, 'Respuesta actualizada exitosamente');
        }

        return Utils::responseJsonError($response, $result['error'] ?? 'Error al actualizar respuesta');
    }

    /**
     * Eliminar una respuesta
     */
    public function eliminar(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $result = $this->respuestaEjercicioModel->delete($id);

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, 'Respuesta eliminada exitosamente');
        }

        return Utils::responseJsonError($response, $result['error'] ?? 'Error al eliminar respuesta');
    }
}
