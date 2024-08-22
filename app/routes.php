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

return function (App $app) {

    //*************************************************************************
    //* RUTA LOGIN
    //*************************************************************************
    $app->post('/login', \App\Login\LoginController::class . ':login');


    //*************************************************************************
    //* CONFIGURACION INICIAL    TPV -> PHPMYADMIN
    //*************************************************************************
    $app->group('/cargaInicial', function (RouteCollectorProxy $group) use ($app) {
        $group->post('/insertarFamilia', \App\Familias\FamiliasController::class . ':insertarFamilia');
        $group->post('/insertarSubfamilia', \App\Subfamilias\SubfamiliasController::class . ':insertarSubfamilia');
        $group->post('/insertarPantallas', \App\Pantallas\PantallasController::class . ':insertarPantallas');
        $group->post('/insertarMarca', \App\Marcas\MarcasController::class . ':insertarMarca');
        $group->post('/insertarIva', \App\Iva\IvaController::class . ':insertarIva');
        $group->post('/insertarArticulo', \App\Articulos\ArticulosController::class . ':insertarArticulo');
    });


    //*************************************************************************
    //* LISTAR DATOS PLANTILLA
    //*************************************************************************

    $app->group('/listarPlantilla', function (RouteCollectorProxy $group) use ($app) {
        $group->get('/listarArticulos', \App\Articulos\ArticulosController::class . ':listarArticulos');
        $group->get('/listarFamilias', \App\Familias\FamiliasController::class . ':listarFamilias');
        $group->get('/listarFamiliasSubfamilia', \App\Familias\FamiliasController::class . ':listarFamiliasSubfamilia');
        $group->get('/listarSubfamilias', \App\Subfamilias\SubfamiliasController::class . ':listarSubfamilias');
        $group->get('/listarSubfamiliasCodigo/{codigo}', \App\Subfamilias\SubfamiliasController::class . ':listarSubfamiliasCodigo');
        $group->get('/listarPantallas', \App\Pantallas\PantallasController::class . ':listarPantallas');
        $group->get('/listarMarcas', \App\Marcas\MarcasController::class . ':listarMarcas');
        $group->get('/listarIvas', \App\Iva\IvaController::class . ':listarIvas');
        $group->get('/obtenerImagenArticuloPlantilla/{codigo:[0-9]+}[/{tipo:[0-9]+}]', \App\Articulos\ArticulosController::class . ':obtenerImagenArticuloPlantilla');
    });


    //*************************************************************************
    //* INSERTAR DATOS PLANTILLA
    //*************************************************************************
    $app->group('/insertarPlantilla', function (RouteCollectorProxy $group) use ($app) {
        $group->post('/insertarArticuloPlantilla', \App\Articulos\ArticulosController::class . ':insertarArticuloPlantilla');
        $group->post('/insertarFamiliaPlaning', \App\Familias\FamiliasController::class . ':insertarFamiliaPlaning');
        $group->post('/insertarSubfamiliaPlaning', \App\Subfamilias\SubfamiliasController::class . ':insertarSubfamiliaPlaning');
        $group->post('/insertarPantallas', \App\Pantallas\PantallasController::class . ':insertarPantallas');
        $group->post('/insertarMarca', \App\Marcas\MarcasController::class . ':insertarMarca');
        $group->post('/insertarIva', \App\Iva\IvaController::class . ':insertarIva');
    });


    //*************************************************************************
    //* ACTUALIZAR DATOS PLANTILLA
    //*************************************************************************
    $app->group('/updatePlantilla', function (RouteCollectorProxy $group) use ($app) {
        $group->post('/actualizarArticuloPlantilla', \App\Articulos\ArticulosController::class . ':actualizarArticuloPlantilla');
        $group->post('/updateFamilia', \App\Familias\FamiliasController::class . ':updateFamilia');
        $group->post('/updateSubfamilia', \App\Subfamilias\SubfamiliasController::class . ':updateSubfamilia');
        $group->post('/updatePantalla', \App\Pantallas\PantallasController::class . ':updatePantalla');
        $group->post('/updateMarca', \App\Marcas\MarcasController::class . ':updateMarca');
        $group->post('/updateIva', \App\Iva\IvaController::class . ':updateIva');
    });


    //*************************************************************************
    //* BORRAR DATOS PLANTILLA
    //*************************************************************************
    $app->group('/deletePlantilla', function (RouteCollectorProxy $group) use ($app) {
        $group->delete('/deleteArticulo/{codigo}', \App\Articulos\ArticulosController::class . ':deleteArticulo');
        $group->delete('/deleteFamilia/{codigo}', \App\Familias\FamiliasController::class . ':deleteFamilia');
        $group->delete('/deleteSubfamilia/{codigo}', \App\Subfamilias\SubfamiliasController::class . ':deleteSubfamilia');
        $group->delete('/borrarPantalla/{codigo}', \App\Pantallas\PantallasController::class . ':borrarPantalla');
        $group->delete('/borrarMarca/{codigo}', \App\Marcas\MarcasController::class . ':borrarMarca');
        $group->delete('/borrarIva/{codigo}', \App\Iva\IvaController::class . ':borrarIva');
    });


    //*************************************************************************
    //* RUTA LOCALES
    //*************************************************************************
    $app->group('/locales', function (RouteCollectorProxy $group) use ($app) {
        $group->get('/listarLocalesAdmin', \App\Local\LocalController::class . ':listarLocalesAdmin');
        $group->get('/listarLocalesUsuario/{usuario}', \App\Local\LocalController::class . ':listarLocalesUsuario');
    });

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
