<?php

namespace App\Modules\Middleware;

use App\Helpers\Utils;
use App\Modules\Services\AuthService;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * Middleware de Lista Negra de Tokens
 * Verifica que el token no esté invalidado (en la tabla tokens_invalidados)
 * Debe usarse después de AuthMiddleware
 */
class TokenBlacklistMiddleware
{
    private $responseFactory;

    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        // Obtener el token del atributo del request (añadido por AuthMiddleware)
        $token = $request->getAttribute('authToken');

        // Si no hay token en los atributos, intentar obtenerlo del header
        if (empty($token)) {
            $authHeader = $request->getHeaderLine('Authorization');
            if (!empty($authHeader) && strpos($authHeader, 'Bearer ') === 0) {
                $token = substr($authHeader, 7);
            }
        }

        // Si aún no hay token, retornar error
        if (empty($token)) {
            $response = $this->responseFactory->createResponse();
            return Utils::responseJsonError($response, 'Token no encontrado', '', 401);
        }

        // Verificar si el token está en la lista negra
        $authService = new AuthService();
        if ($authService->tokenEstaInvalidado($token)) {
            $response = $this->responseFactory->createResponse();
            return Utils::responseJsonError($response, 'Token inválido o sesión cerrada', '', 401);
        }

        // Token válido, continuar con la petición
        return $handler->handle($request);
    }
}
