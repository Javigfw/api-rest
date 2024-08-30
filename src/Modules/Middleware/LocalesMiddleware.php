<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteContext;

use App\Helpers\Utils;
use App\Helpers\Peticiones;

class LocalesMiddleware
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



        if(array_key_exists('local',$args)){            
            $local = intval(Utils::existeVariable($args['local'],0));

            $datosLocal = Peticiones::getLocal($local,['codigo', 'nombre', 'activo']);
            // $datosArticulo = Peticiones::comprobarExisteArticuloPlantilla($articulo);
            if ($datosLocal['codigo'] == 0) {
                $response = $this->responseFactory->createResponse();
                return Utils::responseJsonError($response, 'Local no encontrado');                
            }
            
            $nombre = $datosLocal['nombre'];
            $activo = $datosLocal['activo'];
            
            $request = $request->withAttribute('paramLocalId', $local);
            $request = $request->withAttribute('paramLocalNombre', $nombre);
            $request = $request->withAttribute('paramLocalActivo', $activo);
        }
        return $handler->handle($request);
    }
}
