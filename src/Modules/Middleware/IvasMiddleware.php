<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteContext;

use App\Helpers\Utils;
use App\Helpers\Peticiones;

class IvasMiddleware
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



        if (array_key_exists('iva', $args)) {
            $iva = intval(Utils::existeVariable($args['iva'], 0));

            $datosIva = Peticiones::getIva($iva, ['codigo', 'nombre', 'iva']);
            // $datosArticulo = Peticiones::comprobarExisteArticuloPlantilla($articulo);
            if ($datosIva['codigo'] == 0) {
                $response = $this->responseFactory->createResponse();
                return Utils::responseJsonError($response, 'Iva no encontrado');
            }

            $nombre = $datosIva['nombre'];
            $cantidad = $datosIva['iva'];

            $request = $request->withAttribute('paramIvaId', $iva);
            $request = $request->withAttribute('paramIvaNombre', $nombre);
            $request = $request->withAttribute('paramIvaCantidad', $cantidad);
        }
        return $handler->handle($request);
    }
}
