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
    //* RUTA ARTICULOS
    //*************************************************************************

    $app->get('/listarArticulos', \App\Articulos\ArticulosController::class . ':listarArticulos');
    $app->post('/insertarArticulo', \App\Articulos\ArticulosController::class . ':insertarArticulo');
    $app->post('/insertarArticuloPlantilla', \App\Articulos\ArticulosController::class . ':insertarArticuloPlantilla');
    $app->post('/actualizarArticuloPlantilla', \App\Articulos\ArticulosController::class . ':actualizarArticuloPlantilla');
    $app->delete('/deleteArticulo/{codigo}', \App\Articulos\ArticulosController::class . ':deleteArticulo');
    $app->get('/obtenerImagenArticuloPlantilla/{codigo:[0-9]+}[/{tipo:[0-9]+}]', \App\Articulos\ArticulosController::class . ':obtenerImagenArticuloPlantilla');

    //*************************************************************************
    //* RUTA FAMILIAS
    //*************************************************************************
    $app->get('/listarFamilias', \App\Familias\FamiliasController::class . ':listarFamilias');
    $app->get('/listarFamiliasSubfamilia', \App\Familias\FamiliasController::class . ':listarFamiliasSubfamilia');
    $app->post('/insertarFamilia', \App\Familias\FamiliasController::class . ':insertarFamilia');
    $app->post('/insertarFamiliaPlaning', \App\Familias\FamiliasController::class . ':insertarFamiliaPlaning');
    $app->post('/updateFamilia', \App\Familias\FamiliasController::class . ':updateFamilia');
    $app->delete('/deleteFamilia/{codigo}', \App\Familias\FamiliasController::class . ':deleteFamilia');

    //*************************************************************************
    //* RUTA SUBFAMILIAS
    //*************************************************************************
    $app->get('/listarSubfamilias', \App\Subfamilias\SubfamiliasController::class . ':listarSubfamilias');
    $app->get('/listarSubfamiliasCodigo/{codigo}', \App\Subfamilias\SubfamiliasController::class . ':listarSubfamiliasCodigo');
    $app->post('/insertarSubfamilia', \App\Subfamilias\SubfamiliasController::class . ':insertarSubfamilia');
    $app->post('/insertarSubfamiliaPlaning', \App\Subfamilias\SubfamiliasController::class . ':insertarSubfamiliaPlaning');
    $app->post('/updateSubfamilia', \App\Subfamilias\SubfamiliasController::class . ':updateSubfamilia');
    $app->delete('/deleteSubfamilia/{codigo}', \App\Subfamilias\SubfamiliasController::class . ':deleteSubfamilia');

    //*************************************************************************
    //* RUTA PANTALLAS
    //*************************************************************************
    $app->get('/listarPantallas', \App\Pantallas\PantallasController::class . ':listarPantallas');
    $app->post('/insertarPantallas', \App\Pantallas\PantallasController::class . ':insertarPantallas');
    $app->post('/updatePantalla', \App\Pantallas\PantallasController::class . ':updatePantalla');
    $app->delete('/borrarPantalla/{codigo}', \App\Pantallas\PantallasController::class . ':borrarPantalla');


    //*************************************************************************
    //* RUTA MARCAS
    //*************************************************************************
    $app->get('/listarMarcas', \App\Marcas\MarcasController::class . ':listarMarcas');
    $app->post('/insertarMarca', \App\Marcas\MarcasController::class . ':insertarMarca');
    $app->post('/updateMarca', \App\Marcas\MarcasController::class . ':updateMarca');
    $app->delete('/borrarMarca/{codigo}', \App\Marcas\MarcasController::class . ':borrarMarca');

    //*************************************************************************
    //* RUTA IVAS
    //*************************************************************************
    $app->get('/listarIvas', \App\Iva\IvaController::class . ':listarIvas');
    $app->post('/insertarIva', \App\Iva\IvaController::class . ':insertarIva');
    $app->post('/updateIva', \App\Iva\IvaController::class . ':updateIva');
    $app->delete('/borrarIva/{codigo}', \App\Iva\IvaController::class . ':borrarIva');

    //*************************************************************************
    //* RUTA LOCALES
    //*************************************************************************
    $app->get('/listarLocalesAdmin', \App\Local\LocalController::class . ':listarLocalesAdmin');
    $app->get('/listarLocalesUsuario/{usuario}', \App\Local\LocalController::class . ':listarLocalesUsuario');

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
