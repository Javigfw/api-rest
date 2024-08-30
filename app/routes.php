<?php
// error_reporting(0);
// use Psr\Http\Message\ResponseInterface as Response;
// use Psr\Http\Message\ServerRequestInterface as Request;
// use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

use Slim\Routing\RouteCollectorProxy;
use Slim\App;

// use Slim\Routing\RouteGroup;

// incluir los use de los middleware al final, sino puede dar fallo
use App\Middleware\GrupoMiddleware;
use App\Middleware\ArticulosMiddleware;
use App\Middleware\FamiliasMiddleware;
use App\Middleware\SubfamiliasMiddleware;
use App\Middleware\IvasMiddleware;
use App\Middleware\MarcasMiddleware;
use App\Middleware\PantallasMiddleware;
use App\Middleware\UsuariosMiddleware;
use App\Middleware\LocalesMiddleware;

return function (App $app) {

    //*************************************************************************
    //* RUTA LOGIN
    //*************************************************************************
    $app->post('/login', \App\Login\LoginController::class . ':login');


    //*************************************************************************
    //* CONFIGURACION INICIAL    TPV -> PHPMYADMIN
    //*************************************************************************
    $app->group('/cargaInicial', function (RouteCollectorProxy $group) use ($app) {
        $group->post('/articulo', \App\CargaInicial\CargaInicialController::class . ':insertarArticulo');
        $group->post('/familia', \App\CargaInicial\CargaInicialController::class . ':insertarFamilia');
        $group->post('/subfamilia', \App\CargaInicial\CargaInicialController::class . ':insertarSubfamilia');
        $group->post('/pantallas', \App\CargaInicial\CargaInicialController::class . ':insertarPantallas');
        $group->post('/marca', \App\CargaInicial\CargaInicialController::class . ':insertarMarca');
        $group->post('/iva', \App\CargaInicial\CargaInicialController::class . ':insertarIva');
    });


    //*************************************************************************
    //* GRUPO PLANTILLA
    //*************************************************************************
    $app->group('/plantilla', function (RouteCollectorProxy $plantilla) use ($app) {

        // articulo
        $plantilla->group('/articulos', function (RouteCollectorProxy $articulos) use ($app) {
            $articulos->get('', \App\Articulos\ArticulosController::class . ':listar');
            $articulos->post('', \App\Articulos\ArticulosController::class . ':insertar');
            $articulos->put('/{articulo:[0-9]+}', \App\Articulos\ArticulosController::class . ':actualizar');
            $articulos->delete('/{articulo:[0-9]+}', \App\Articulos\ArticulosController::class . ':borrar');
            $articulos->get('/{articulo:[0-9]+}[/{tipo:[0-9]+}]', \App\Articulos\ArticulosController::class . ':leer');
        })->add(new ArticulosMiddleware($app->getResponseFactory()));

        // familia
        $plantilla->group('/familias', function (RouteCollectorProxy $familias) use ($app) {
            $familias->get('', \App\Familias\FamiliasController::class . ':listar');
            $familias->post('', \App\Familias\FamiliasController::class . ':insertar');
            $familias->put('/{familia}', \App\Familias\FamiliasController::class . ':actualizar');
            $familias->delete('/{familia}', \App\Familias\FamiliasController::class . ':borrar');
            $familias->get('/subfamilias', \App\Subfamilias\SubfamiliasController::class . ':listar');
            // subfamilia
            $familias->group('/{familia:[0-9]+}/subfamilias', function (RouteCollectorProxy $subfamilias) use ($app) {
                $subfamilias->get('', \App\Subfamilias\SubfamiliasController::class . ':listar');
                $subfamilias->post('', \App\Subfamilias\SubfamiliasController::class . ':insertar');
                $subfamilias->put('/{subfamilia:[0-9]+}', \App\Subfamilias\SubfamiliasController::class . ':actualizar');
                $subfamilias->delete('/{subfamilia:[0-9]+}', \App\Subfamilias\SubfamiliasController::class . ':borrar');
            })->add(new SubfamiliasMiddleware($app->getResponseFactory()));
        })->add(new FamiliasMiddleware($app->getResponseFactory()));

        // pantalla
        $plantilla->group('/pantallas', function (RouteCollectorProxy $pantalla) use ($app) {
            $pantalla->get('', \App\Pantallas\PantallasController::class . ':listar');
            $pantalla->post('', \App\Pantallas\PantallasController::class . ':insertar');
            $pantalla->put('/{pantalla:[0-9]+}', \App\Pantallas\PantallasController::class . ':actualizar');
            $pantalla->delete('/{pantalla:[0-9]+}', \App\Pantallas\PantallasController::class . ':borrar');
        })->add(new PantallasMiddleware($app->getResponseFactory()));

        // iva
        $plantilla->group('/ivas', function (RouteCollectorProxy $iva) use ($app) {
            $iva->get('', \App\Iva\IvaController::class . ':listar');
            $iva->post('', \App\Iva\IvaController::class . ':insertar');
            $iva->put('/{iva:[0-9]+}', \App\Iva\IvaController::class . ':actualizar');
            $iva->delete('/{iva:[0-9]+}', \App\Iva\IvaController::class . ':borrar');
        })->add(new IvasMiddleware($app->getResponseFactory()));

        // marca
        $plantilla->group('/marcas', function (RouteCollectorProxy $marca) use ($app) {
            $marca->get('', \App\Marcas\MarcasController::class . ':listar');
            $marca->post('', \App\Marcas\MarcasController::class . ':insertar');
            $marca->put('/{marca:[0-9]+}', \App\Marcas\MarcasController::class . ':actualizar');
            $marca->delete('/{marca:[0-9]+}', \App\Marcas\MarcasController::class . ':borrar');
        })->add(new MarcasMiddleware($app->getResponseFactory()));
    });


    //*************************************************************************
    //* GRUPO ADMINISTRACION LOCALES
    //*************************************************************************
    $app->group('/locales', function (RouteCollectorProxy $locales) use ($app) {
        //$locales->get('', \App\Local\LocalController::class . ':listar'); // lista todos los locales
        $locales->get('/{local}/usuarios', \App\Local\LocalController::class . ':listarUsuariosLocal'); // lista los usuarios del local, con y sin acceso
        $locales->get('/{usuario:[0-9]+}', \App\Local\LocalController::class . ':listar'); // lista los locales del usuario
        $locales->post('', \App\Local\LocalController::class . ':insertar');
        $locales->put('/{local:[0-9]+}', \App\Local\LocalController::class . ':actualizar');
        $locales->put('/desactivar/{local:[0-9]+}', \App\Local\LocalController::class . ':desactivar');
        $locales->delete('/{local:[0-9]+}/{usuario:[0-9]+}', \App\Local\LocalController::class . ':desactivarAcceso');
        $locales->post('/activar', \App\Local\LocalController::class . ':activarAcceso');

    })->add(new LocalesMiddleware($app->getResponseFactory()));

    //*************************************************************************
    //* GRUPO USUARIOS
    //*************************************************************************
    $app->group('/usuarios', function (RouteCollectorProxy $usuario) use ($app) {
        $usuario->get('', \App\Local\LocalController::class . ':listar'); // lista todos los usuarios
        $usuario->get('{usuario}/locales', \App\Local\LocalController::class . ':listar'); // lista todos los locales del usuario
        



    })->add(new UsuariosMiddleware($app->getResponseFactory()));
    //*************************************************************************
    //* GRUPO LOCALES
    //*************************************************************************
    // $app->group('/locales', function (RouteCollectorProxy $group) use ($app) {
    //     //$group->get('/{usuario:[0-9]+}', \App\Local\LocalController::class . ':listar');
    // });

    //*************************************************************************
    //* RUTAS GRUPO ADMINISTRADORES
    //*************************************************************************

    /*$app->group('/usuarios', function (RouteCollectorProxy $group) use ($app) {
        // NOTA consulta grupo con dos parametros opcionales (se pueden incluir o no)
        $group->post('/listar[/{saltoRegistros:[0-9]+}[/{registros:[0-9]+}]]', \App\Usuarios\UsuariosController::class . ':listar');
        $group->post('/leer[/{id:[0-9]+}]', \App\Usuarios\UsuariosController::class . ':leer');
        $group->post('/listarProvincias', \App\Usuarios\UsuariosController::class . ':listarProvincias');
        $group->post('/guardar', \App\Usuarios\UsuariosController::class . ':guardar');
        $group->put('/desactivaUsuario', \App\Usuarios\UsuariosController::class . ':desactivaUsuario');
        //$group->put('/desactivaUsuario', \App\Usuarios\UsuariosController::class . ':desactivaUsuario');
        $group->delete('/borrar', \App\Usuarios\UsuariosController::class . ':borrar');
    })->add(new GrupoMiddleware($app->getResponseFactory()));
*/



    //******************************************************************************************************************
    //*                                        EJEMPLOS DE PRUEBA OMITIDOS
    //******************************************************************************************************************

    /*
    //REGISTRO
    $app->post('/register', \App\Authentication\RegisterController::class . ':registerWebhookDeliverect');
    // $app->post('/prueba/{local}', \App\Orders\OrdersController::class . ':prueba')->add(new \App\Middleware\PruebaMiddleware($app->getResponseFactory()));
    // $app->post('/prueba/{local}/{app_type}', \App\Orders\OrdersController::class . ':prueba')->add(new LocalMiddleware($app->getResponseFactory()))->add(new AppTypeMiddleware($app->getResponseFactory()));
    // ->add(function (Request $request, RequestHandler $handler) {
    //     // Add the session storage to your request as [READ-ONLY]        
    //     $request = $request->withAttribute('isLocal', '$_SESSION');        
    //     return $handler->handle($request);
    // });


    //PRODUCTOS
    //$app->get('/{local}/products', \App\Products\ProductsController::class . ':products');

    $app->group('/{local}', function (RouteCollectorProxy $group) use ($app) {
        $group->post('/completarRegistro/{app_type}', \App\Authentication\RegisterController::class . ':completarRegistro')->add(new AppTypeMiddleware($app->getResponseFactory()));
        $group->post('/token/{app_type}', \App\Authentication\AuthenticationController::class . ':token')->add(new AppTypeMiddleware($app->getResponseFactory()));
        $group->get('/products', \App\Products\ProductsController::class . ':productsWebhookDeliverect');
        $group->post('/orders', \App\Orders\OrdersController::class . ':ordersWebhookDeliverect');
        $group->post('/downloadOrders', \App\Orders\OrdersController::class . ':downloadOrders');
        $group->post('/markOrderAsDownloaded', \App\Orders\OrdersController::class . ':markOrderAsDownloaded');
    })->add(new LocalMiddleware($app->getResponseFactory()));
    
    $app->group('/{app_type}', function (RouteCollectorProxy $group) use ($app) {
        // $group->post('/register-local/{local}', \App\Authentication\RegisterController::class . ':completarRegistro')->add(new LocalMiddleware($app->getResponseFactory()));        
        $group->post('/products/{local}', \App\Products\ProductsController::class . ':productsPost')->add(new LocalMiddleware($app->getResponseFactory()));
        $group->get('/allergens/{local}', \App\Products\ProductsController::class . ':getAllergens')->add(new LocalMiddleware($app->getResponseFactory()));
        $group->get('/brands/{local}', \App\Orders\OrdersController::class . ':getBrands')->add(new LocalMiddleware($app->getResponseFactory()));
        $group->post('/order-status/{local}', \App\Orders\OrdersController::class . ':setStatus')->add(new LocalMiddleware($app->getResponseFactory()));
        $group->post('/order-preparation-time/{local}', \App\Orders\OrdersController::class . ':updatePreparationTime')->add(new LocalMiddleware($app->getResponseFactory()));
        $group->get('/all-channels/{local}', \App\Orders\OrdersController::class . ':getAllChannels')->add(new LocalMiddleware($app->getResponseFactory()));
        $group->get('/channel-links/{local}', \App\Orders\OrdersController::class . ':getChannelLinks')->add(new LocalMiddleware($app->getResponseFactory()));
        // $group->post('/saludo/{local}', \App\Authentication\AuthenticationController::class . ':saludo')->add(new LocalMiddleware($app->getResponseFactory()));        
    })->add(new AppTypeMiddleware($app->getResponseFactory()));
*/
    /*
    ->add(function (Request $request, RequestHandler $handler) use ($app) {
        $response = $handler->handle($request);
        $dateOrTime = (string) $response->getBody();
    
        $response = $app->getResponseFactory()->createResponse();
        $response->getBody()->write('¡' . $dateOrTime . '!');
    
        return $response;
    });
    */
    /*
        $app->post('/login',        \App\Login\LoginController::class . ':login');
        $app->get('/saludo',        \App\Pruebas\PruebasController::class . ':saludo');
        $app->get('/auth',          \App\Pruebas\PruebasController::class . ':auth');


        $app->group('/empleado', function (RouteCollectorProxy $group) {
            $group->post('/crear',                          \App\Empleados\EmpleadosController::class . ':crear');
            $group->get('/leer/{id}',                       \App\Empleados\EmpleadosController::class . ':leer');
            $group->put('/actualizar/{id}',                 \App\Empleados\EmpleadosController::class . ':actualizar');
            $group->get('/listar[/{cantidad}[/{empieza}]]', \App\Empleados\EmpleadosController::class . ':listar');
            $group->delete('/borrar/{id}',                  \App\Empleados\EmpleadosController::class . ':borrar');
        });

        $app->map(['GET', 'POST', 'PUT', 'DELETE'], '/empleado2[/{p1}]', \App\Empleados\EmpleadosController::class . ':completa');

    */
};
