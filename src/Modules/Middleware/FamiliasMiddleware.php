<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteContext;

use App\Helpers\Utils;
use App\Helpers\Peticiones;

class FamiliasMiddleware
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



        if (array_key_exists('familia', $args)) {
            $familia = intval(Utils::existeVariable($args['familia'], 0));

            $datosFamilia = Peticiones::getFamilia($familia, ['codigo', 'nombre']);
            // $datosArticulo = Peticiones::comprobarExisteArticuloPlantilla($articulo);
            if ($datosFamilia['codigo'] == 0) {
                $response = $this->responseFactory->createResponse();
                return Utils::responseJsonError($response, 'Familia no encontrada');
            }

            $nombre = $datosFamilia['nombre'];

            $request = $request->withAttribute('paramFamiliaId', $familia);
            $request = $request->withAttribute('paramFamiliaDescripcion', $nombre);
        }
        return $handler->handle($request);
    }
}
