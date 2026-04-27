<?php

namespace App\Modules\Controllers;

use App\Helpers\Controller;
use App\Helpers\Utils;
use App\Modules\Models\PlanSuscripcionModel;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Controlador para la gestión de planes de suscripción
 */
class PlanSuscripcionController extends Controller
{
    protected PlanSuscripcionModel $planModel;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->planModel = new PlanSuscripcionModel();
    }

    /**
     * Listar todos los planes de suscripción disponibles
     * 
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function getPlanesDisponibles(Request $request, Response $response): Response
    {
        $result = $this->planModel->findActivos();

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, [
                'success' => true,
                'planes' => $result['data']
            ]);
        }

        return Utils::responseJsonError($response, 'Error al obtener planes de suscripción');
    }

    /**
     * Obtener un plan específico
     */
    public function get(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $result = $this->planModel->find($id);

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, [
                'success' => true,
                'plan' => $result['data']
            ]);
        }

        return Utils::responseJsonError($response, 'Plan no encontrado', '', 404);
    }

    /**
     * Crear un nuevo plan de suscripción
     */
    public function create(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        
        if (empty($data['nombre']) || empty($data['precio']) || empty($data['duracion_dias'])) {
            return Utils::responseJsonError($response, 'Datos incompletos (nombre, precio, duracion_dias son requeridos)', '', 400);
        }

        $result = $this->planModel->create($data);

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, [
                'success' => true,
                'message' => 'Plan creado correctamente',
                'id' => $result['data']
            ], 201);
        }

        return Utils::responseJsonError($response, 'Error al crear plan: ' . ($result['error'] ?? ''));
    }

    /**
     * Actualizar un plan de suscripción
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $data = $request->getParsedBody();

        $result = $this->planModel->update($id, $data);

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, [
                'success' => true,
                'message' => 'Plan actualizado correctamente'
            ]);
        }

        return Utils::responseJsonError($response, 'Error al actualizar plan');
    }

    /**
     * Eliminar un plan de suscripción
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $result = $this->planModel->delete($id);

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, [
                'success' => true,
                'message' => 'Plan eliminado correctamente'
            ]);
        }

        return Utils::responseJsonError($response, 'Error al eliminar plan');
    }
}
