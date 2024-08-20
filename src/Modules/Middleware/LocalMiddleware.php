<?php

namespace APP\Middleware;

use App\Helpers\Utils;
use App\Helpers\Data;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteContext;

class LocalMiddleware
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

        // $localNombre = Utils::existeVariable($args['local']);

        // $datosLocal = Data::getLocal($localNombre, ['id', 'accountid', 'locationid', 'clientid', 'clientsecret', 'ver_filtro_alergenos', 'deliverect']);
        // if (sizeof($datosLocal) == 0) {
        //     $response = $this->responseFactory->createResponse();
        //     return Utils::responseJsonError($response, 'Local no encontrado', $datosLocal, 401);
        // }

        // $local = $datosLocal['id'];
        // $accountId = $datosLocal['accountid'];
        // $locationId = $datosLocal['locationid'];
        // $clientId = $datosLocal['clientid'];
        // $clientSecret = $datosLocal['clientsecret'];
        // $conAlergenos = $datosLocal['ver_filtro_alergenos'] == 1;
        // $conDeliverect = $datosLocal['deliverect'] == 1;

        // $request = $request->withAttribute('paramLocal', $local);
        // $request = $request->withAttribute('paramAccountId', $accountId);
        // $request = $request->withAttribute('paramLocationId', $locationId);
        // $request = $request->withAttribute('paramClientId', $clientId);
        // $request = $request->withAttribute('paramClientSecret', $clientSecret);
        // $request = $request->withAttribute('paramConAlergenos', $conAlergenos);
        // $request = $request->withAttribute('paramConDeliverect', $conDeliverect);
        return $handler->handle($request);
    }
}
