<?php

namespace App\Modules\Middleware;

use App\Helpers\Utils;
use App\Modules\Models\SuscripcionModel;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * Middleware de Suscripción
 * Verifica que el usuario autenticado tenga una suscripción activa
 */
class SuscripcionMiddleware
{
    private $responseFactory;
    private SuscripcionModel $suscripcionModel;

    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
        $this->suscripcionModel = new SuscripcionModel();
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $jwt = $request->getAttribute('jwt');

        if (!$jwt) {
            $response = $this->responseFactory->createResponse();
            return Utils::responseJsonError($response, 'Usuario no autenticado', '', 401);
        }

        $usuarioId = (int)$jwt->data->id;

        // Verificar si el usuario es admin (los admins suelen tener acceso a todo)
        $esAdmin = (bool)($jwt->data->esAdmin ?? false);

        if (!$esAdmin && !$this->suscripcionModel->usuarioTieneSuscripcionActiva($usuarioId)) {
            $response = $this->responseFactory->createResponse();
            return Utils::responseJsonError($response, 'Acceso denegado: se requiere una suscripción activa', '', 403);
        }

        return $handler->handle($request);
    }
}
