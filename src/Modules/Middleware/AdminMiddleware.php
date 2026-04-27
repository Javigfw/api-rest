<?php

namespace App\Modules\Middleware;

use App\Helpers\Utils;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * Middleware de Administrador
 * Verifica que el usuario autenticado tenga privilegios de administrador
 */
class AdminMiddleware
{
    private $responseFactory;

    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $jwt = $request->getAttribute('jwt');

        if (!$jwt || !isset($jwt->data->esAdmin) || (int)$jwt->data->esAdmin !== 1) {
            $response = $this->responseFactory->createResponse();
            return Utils::responseJsonError($response, 'Acceso denegado: se requieren privilegios de administrador', '', 403);
        }

        return $handler->handle($request);
    }
}
