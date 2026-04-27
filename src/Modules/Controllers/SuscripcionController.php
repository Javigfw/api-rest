<?php

namespace App\Modules\Controllers;

use App\Helpers\Controller;
use App\Helpers\Utils;
use App\Modules\Models\SuscripcionModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;

/**
 * Controlador de Suscripciones
 */
class SuscripcionController extends Controller
{
    protected SuscripcionModel $suscripcionModel;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->suscripcionModel = new SuscripcionModel();
    }

    /**
     * Listar todas las suscripciones (Admin)
     */
    public function listar(Request $request, Response $response, array $args): Response
    {
        // Obtener parámetros de búsqueda
        $queryParams = $request->getQueryParams();
        $search = $queryParams['search'] ?? '';
        $dateFrom = $queryParams['dateFrom'] ?? null;
        $dateTo = $queryParams['dateTo'] ?? null;

        // Usar método del modelo que devuelve todas las suscripciones con JOIN y filtros
        $result = $this->suscripcionModel->findAllWithDetails($search, $dateFrom, $dateTo);

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, $result['data']);
        }

        return Utils::responseJsonError($response, 'Error al obtener suscripciones');
    }

    /**
     * Ver detalle de una suscripción específica (Admin)
     */
    public function ver(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];

        $result = $this->suscripcionModel->findByIdWithDetails($id);

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, $result['data']);
        }

        return Utils::responseJsonError($response, $result['error'] ?? 'Suscripción no encontrada', 404);
    }
}
