<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteContext;

use App\Helpers\Utils;
use App\Helpers\Peticiones;

class SubfamiliasMiddleware
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



        if (array_key_exists('subfamilia', $args)) {
            $subfamilia = intval(Utils::existeVariable($args['subfamilia'], 0));

            $datosSubfamilia = Peticiones::getSubfamilia($subfamilia, ['codigo', 'nombre', 'familia']);
            // $datosArticulo = Peticiones::comprobarExisteArticuloPlantilla($articulo);
            if ($datosSubfamilia['codigo'] == 0) {
                $response = $this->responseFactory->createResponse();
                return Utils::responseJsonError($response, 'Subfamilia no encontrada');
            }

            $nombre = $datosSubfamilia['nombre'];
            $familia = $datosSubfamilia['familia'];

            $request = $request->withAttribute('paramSubfamiliaId', $subfamilia);
            $request = $request->withAttribute('paramSubfamiliaNombre', $nombre);
            $request = $request->withAttribute('paramSubfamiliaFamilia', $familia);
        }
        return $handler->handle($request);
    }
}
