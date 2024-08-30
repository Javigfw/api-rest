<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteContext;

use App\Helpers\Utils;
use App\Helpers\Peticiones;

class PantallasMiddleware
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



        if (array_key_exists('pantalla', $args)) {
            $pantalla = intval(Utils::existeVariable($args['pantalla'], 0));

            $datosPantalla = Peticiones::getPantalla($pantalla, ['codigo', 'nombre']);
            // $datosArticulo = Peticiones::comprobarExisteArticuloPlantilla($articulo);
            if ($datosPantalla['codigo'] == 0) {
                $response = $this->responseFactory->createResponse();
                return Utils::responseJsonError($response, 'Pantalla no encontrada');
            }

            $nombre = $datosPantalla['nombre'];

            $request = $request->withAttribute('paramPantallaId', $pantalla);
            $request = $request->withAttribute('paramPantallaNombre', $nombre);
        }
        return $handler->handle($request);
    }
}
