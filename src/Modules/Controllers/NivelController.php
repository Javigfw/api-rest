<?php

namespace App\Modules\Controllers;

use App\Helpers\Controller;
use App\Helpers\Utils;
use App\Modules\Models\NivelModel;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Controlador de Niveles (Tests)
 */
class NivelController extends Controller
{
    protected NivelModel $nivelModel;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->nivelModel = new NivelModel();
    }

    /**
     * Listar todos los niveles
     */
    public function listar(Request $request, Response $response, array $args): Response
    {
        $queryParams = $request->getQueryParams();
        $idBloque = $queryParams['idBloque'] ?? null;
        
        if ($idBloque) {
            $result = $this->nivelModel->findByBloque((int)$idBloque);
        } else {
            $result = $this->nivelModel->findAll();
        }

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, $result['data']);
        }

        return Utils::responseJsonError($response, 'Error al listar niveles');
    }

    /**
     * Ver detalle de un nivel
     */
    public function ver(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $result = $this->nivelModel->find($id);

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, $result['data']);
        }

        return Utils::responseJsonError($response, $result['error'] ?? 'Nivel no encontrado', '', 404);
    }

    /**
     * Crear un nuevo nivel
     */
    public function crear(Request $request, Response $response, array $args): Response
    {
        $params = $request->getParsedBody();
        
        $mensaje = Utils::requiredParams(['idBloque', 'nivel'], $params);
        if ($mensaje != '') {
            return Utils::responseJsonError($response, $mensaje);
        }

        $result = $this->nivelModel->create($params);

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, 'Nivel creado exitosamente', 201);
        }

        return Utils::responseJsonError($response, $result['error'] ?? 'Error al crear nivel');
    }

    /**
     * Editar un nivel
     */
    public function editar(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $params = $request->getParsedBody();

        if (empty($params)) {
            return Utils::responseJsonError($response, 'No se enviaron datos para actualizar');
        }

        $result = $this->nivelModel->update($id, $params);

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, 'Nivel actualizado exitosamente');
        }

        return Utils::responseJsonError($response, $result['error'] ?? 'Error al actualizar nivel');
    }

    /**
     * Eliminar un nivel
     */
    public function eliminar(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $result = $this->nivelModel->delete($id);

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, 'Nivel eliminado exitosamente');
        }

        return Utils::responseJsonError($response, $result['error'] ?? 'Error al eliminar nivel');
    }
}
