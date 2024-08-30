<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteContext;

use App\Helpers\Utils;
use App\Helpers\Peticiones;

class UsuariosMiddleware
{
    private $responseFactory;

    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        // obtengo los argumentos de la ruta y comprueba si existe el articulo
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $args = $route->getArguments();



        if (array_key_exists('usuario', $args)) {
            $usuario = intval(Utils::existeVariable($args['usuario'], 0));

            $datosUsuario = Peticiones::getUsuario($usuario, ['codigo', 'email', 'es_admin']);
            // $datosArticulo = Peticiones::comprobarExisteArticuloPlantilla($articulo);
            if ($datosUsuario['codigo'] == 0) {
                $response = $this->responseFactory->createResponse();
                return Utils::responseJsonError($response, 'Usuario no encontrado');
            }

            $nombre = $datosUsuario['nombre'];

            $request = $request->withAttribute('paramUsuarioId', $usuario);
            $request = $request->withAttribute('paramUsuarioNombre', $nombre);
        }
        return $handler->handle($request);
    }
}
