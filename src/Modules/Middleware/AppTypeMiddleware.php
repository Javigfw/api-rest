<?php

namespace APP\Middleware;

use App\Helpers\Utils;
use App\Helpers\Data;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteContext;

class AppTypeMiddleware
{
    private $responseFactory;

    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        // // obtengo los argumentos de la ruta y comprueba si existe el tipo de app
        // $routeContext = RouteContext::fromRequest($request);
        // $route = $routeContext->getRoute();
        // $args = $route->getArguments();

        // $appTypeNombre = Utils::existeVariable($args['app_type']);

        // $appType = Data::getAppType($appTypeNombre);
        // if ($appType == 0) {
        //     $response = $this->responseFactory->createResponse();
        //     return Utils::responseJsonError($response, 'Aplicación no encontrada', '', 401);
        // }
        // $request = $request->withAttribute('paramAppType', $appType);

        return $handler->handle($request);
    }
}
