<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteContext;

use App\Helpers\Utils;
use App\Helpers\Peticiones;

class MarcasMiddleware
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



        if (array_key_exists('marca', $args)) {
            $marca = intval(Utils::existeVariable($args['marca'], 0));

            $datosMarca = Peticiones::getMarca($marca, ['codigo', 'nombre']);
            // $datosArticulo = Peticiones::comprobarExisteArticuloPlantilla($articulo);
            if ($datosMarca['codigo'] == 0) {
                $response = $this->responseFactory->createResponse();
                return Utils::responseJsonError($response, 'Marca no encontrada');
            }

            $nombre = $datosMarca['nombre'];

            $request = $request->withAttribute('paramMarcaId', $marca);
            $request = $request->withAttribute('paramMarcaNombre', $nombre);
        }
        return $handler->handle($request);
    }
}
