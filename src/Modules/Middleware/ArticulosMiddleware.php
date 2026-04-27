<?php

namespace App\Modules\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteContext;


use App\Helpers\Middleware;
use App\Helpers\Utils;

class ArticulosMiddleware
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



        if (array_key_exists('articulo', $args)) {
            $articulo = intval(Utils::existeVariable($args['articulo'], 0));

            $datosArticulo = Middleware::getArticulo($articulo, ['codigo', 'descripcion', 'precio']);
            // $datosArticulo = Peticiones::comprobarExisteArticuloPlantilla($articulo);
            if ($datosArticulo['codigo'] == 0) {
                $response = $this->responseFactory->createResponse();
                return Utils::responseJsonError($response, 'Articulo no encontrado');
            }

            $descripcion = $datosArticulo['descripcion'];

            $request = $request->withAttribute('paramArticuloId', $articulo);
            $request = $request->withAttribute('paramArticuloDescripcion', $descripcion);
            $request = $request->withAttribute('paramArticuloPrecio', $datosArticulo['precio']);
        }
        return $handler->handle($request);
    }
}
