<?php

namespace App\Modules\Middleware;

use App\Helpers\Utils;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * Middleware de Autenticación
 * Verifica que las peticiones incluyan un token válido en el header Authorization
 */
class AuthMiddleware
{
    private $responseFactory;

    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        // Obtener el token del header Authorization
        $authHeader = $request->getHeaderLine('Authorization');
        
        // Verificar que el header existe
        if (empty($authHeader)) {
            $response = $this->responseFactory->createResponse();
            return Utils::responseJsonError($response, 'Token no proporcionado', '', 401);
        }

        // Extraer el token (formato: "Bearer [token]")
        $token = $authHeader;
        if (strpos($authHeader, 'Bearer ') === 0) {
            $token = substr($authHeader, 7); // Remover "Bearer "
        }

        // Verificar que el token no esté vacío
        if (empty($token)) {
            $response = $this->responseFactory->createResponse();
            return Utils::responseJsonError($response, 'Token inválido', '', 401);
        }

        // Verificar token JWT usando la clave secreta
        try {
            // Decodificar valida firma y expiración
            $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key(\App\Modules\Services\AuthService::JWT_SECRET, \App\Modules\Services\AuthService::JWT_ALGO));
            
            // Si llegamos aquí, el token es válido
            // Podemos agregar datos del usuario al request
            $request = $request->withAttribute('authToken', $token);
            $request = $request->withAttribute('jwt', $decoded); // También pasamos el objeto decodificado
            
        } catch (\Exception $e) {
            $response = $this->responseFactory->createResponse();
            return Utils::responseJsonError($response, 'Token inválido o expirado: ' . $e->getMessage(), '', 401);
        }
        
        return $handler->handle($request);
    }
}
