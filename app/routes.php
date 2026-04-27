<?php
// error_reporting(0);
// use Psr\Http\Message\ResponseInterface as Response;
// use Psr\Http\Message\ServerRequestInterface as Request;
// use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

use Slim\Routing\RouteCollectorProxy;
use Slim\App;

// incluir los use de los middleware al final, sino puede dar fallo
use App\Modules\Middleware\ArticulosMiddleware;
use App\Modules\Middleware\AuthMiddleware;
use App\Modules\Middleware\TokenBlacklistMiddleware;
use App\Modules\Middleware\AdminMiddleware;
use App\Modules\Middleware\SuscripcionMiddleware;


return function (App $app) {


    //*************************************************************************
    //* RUTAS DE AUTENTICACIÓN
    //*************************************************************************

    // Grupo de Autenticación
    $app->group('/auth', function (RouteCollectorProxy $group) {
        $group->post('/login', \App\Modules\Controllers\AuthController::class . ':login');
        $group->post('/register', \App\Modules\Controllers\AuthController::class . ':registrar');
        $group->post('/forgot-password', \App\Modules\Controllers\AuthController::class . ':forgotPassword');
        $group->post('/reset-password', \App\Modules\Controllers\AuthController::class . ':resetPassword');
        $group->get('/verify-email', \App\Modules\Controllers\AuthController::class . ':verificarEmail'); // Check email availability
        $group->post('/validate-register', \App\Modules\Controllers\AuthController::class . ':validar');  // Validate form fields

        // Rutas protegidas de Auth
        $group->post('/logout', \App\Modules\Controllers\AuthController::class . ':logout')
            ->add(TokenBlacklistMiddleware::class)
            ->add(AuthMiddleware::class);

        $group->get('/me', \App\Modules\Controllers\AuthController::class . ':me')
            ->add(TokenBlacklistMiddleware::class)
            ->add(AuthMiddleware::class);

        $group->get('/check-status', \App\Modules\Controllers\AuthController::class . ':checkAdminStatus')
            ->add(TokenBlacklistMiddleware::class)
            ->add(AuthMiddleware::class);
    });

    // Rutas Trial / Desafío (Públicas o Protegidas según necesidad, usuario no especificó auth)
    // El código original no tenía auth headers, así que las dejaremos públicas por ahora o 
    // bajo un grupo opcional. Asumiremos públicas similar a reviews.
    $app->group('/trial', function (RouteCollectorProxy $group) {
        $group->post('/questions', \App\Modules\Controllers\TrialController::class . ':getQuestions');
        $group->post('/check', \App\Modules\Controllers\TrialController::class . ':checkAnswer');
        $group->post('/sync', \App\Modules\Controllers\TrialController::class . ':syncAnswers')
            ->add(TokenBlacklistMiddleware::class)
            ->add(AuthMiddleware::class);
    });

    // --- RESEÑAS ---
    $app->group('/resenas', function (RouteCollectorProxy $group) {
        $group->get('/aleatorias', \App\Modules\Controllers\ResenaController::class . ':aleatorias');
        $group->post('', \App\Modules\Controllers\ResenaController::class . ':crear')
            ->add(TokenBlacklistMiddleware::class)
            ->add(AuthMiddleware::class);
    });

    // --- ASSESSMENT ---
    $app->group('/assessment', function (RouteCollectorProxy $group) {
        // Públicas
        $group->get('/preguntas', \App\Modules\Controllers\AssessmentController::class . ':getPreguntas');
        $group->post('/calcular', \App\Modules\Controllers\AssessmentController::class . ':calcular');

        // Protegidas (requieren autenticación)
        $group->post('/asignar-bloques', \App\Modules\Controllers\AssessmentController::class . ':asignarBloques')
            ->add(TokenBlacklistMiddleware::class)
            ->add(AuthMiddleware::class);
    });

    // --- BLOQUES ---
    $app->group('/bloques', function (RouteCollectorProxy $group) {
        $group->get('', \App\Modules\Controllers\BloqueController::class . ':listar');
        $group->get('/{id:[0-9]+}', \App\Modules\Controllers\BloqueController::class . ':ver');

        // Rutas específicas
        $group->get('/nivel/{nivelId:[0-9]+}', \App\Modules\Controllers\BloqueController::class . ':obtenerPorNivel');
        $group->get('/progreso', \App\Modules\Controllers\BloqueController::class . ':obtenerConProgreso');
        $group->get('/bloques-suscripcion', \App\Modules\Controllers\BloqueController::class . ':obtenerPorSuscripcion');
        $group->get('/progreso-niveles-ejercicios', \App\Modules\Controllers\BloqueController::class . ':obtenerProgresoNivelesEjercicios');
        $group->get('/filtrar-ejercicios', \App\Modules\Controllers\BloqueController::class . ':filtrarEjerciciosBloque');
        // NOTE: Access check (subscription OR trial blocks) is done inside the controller
        $group->get('/{id:[0-9]+}/niveles', \App\Modules\Controllers\BloqueController::class . ':getNiveles');
        $group->post('/corregir', \App\Modules\Controllers\BloqueController::class . ':corregirTest');

        // Rutas administrativas
        $group->group('', function (RouteCollectorProxy $adminGroup) {
            $adminGroup->post('', \App\Modules\Controllers\BloqueController::class . ':crear');
            $adminGroup->put('/{id:[0-9]+}', \App\Modules\Controllers\BloqueController::class . ':editar');
            $adminGroup->delete('/{id:[0-9]+}', \App\Modules\Controllers\BloqueController::class . ':eliminar');
        })->add(AdminMiddleware::class);

    })->add(TokenBlacklistMiddleware::class)
        ->add(AuthMiddleware::class);

    // Rutas de Foro (Protegidas)
    $app->group('/foro', function (RouteCollectorProxy $group) {
        $group->get('/mensajes', \App\Modules\Controllers\ForoController::class . ':getMensajes');
        $group->post('/mensajes', \App\Modules\Controllers\ForoController::class . ':createMensaje')
            ->add(SuscripcionMiddleware::class);
        $group->get('/usuario/{id:[0-9]+}', \App\Modules\Controllers\ForoController::class . ':getMensajesUsuario');

        // Rutas administrativas
        $group->group('', function (RouteCollectorProxy $adminGroup) {
            $adminGroup->delete('/mensajes/{id:[0-9]+}', \App\Modules\Controllers\ForoController::class . ':eliminarMensaje');
        })->add(AdminMiddleware::class);

    })->add(TokenBlacklistMiddleware::class)
        ->add(AuthMiddleware::class);

    // --- NIVELES (Tests) ---
    $app->group('/niveles', function (RouteCollectorProxy $group) {
        $group->get('', \App\Modules\Controllers\NivelController::class . ':listar');
        $group->get('/{id:[0-9]+}', \App\Modules\Controllers\NivelController::class . ':ver');

        $group->group('', function (RouteCollectorProxy $adminGroup) {
            $adminGroup->post('', \App\Modules\Controllers\NivelController::class . ':crear');
            $adminGroup->put('/{id:[0-9]+}', \App\Modules\Controllers\NivelController::class . ':editar');
            $adminGroup->delete('/{id:[0-9]+}', \App\Modules\Controllers\NivelController::class . ':eliminar');
        })->add(AdminMiddleware::class);
    })->add(TokenBlacklistMiddleware::class)
        ->add(AuthMiddleware::class);

    // --- EJERCICIOS ---
    $app->group('/ejercicios', function (RouteCollectorProxy $group) {
        $group->get('', \App\Modules\Controllers\EjercicioController::class . ':listar');
        $group->get('/{id:[0-9]+}', \App\Modules\Controllers\EjercicioController::class . ':ver');

        $group->group('', function (RouteCollectorProxy $adminGroup) {
            $adminGroup->get('/buscar', \App\Modules\Controllers\EjercicioController::class . ':buscar');
            $adminGroup->post('', \App\Modules\Controllers\EjercicioController::class . ':crear');
            $adminGroup->put('/{id:[0-9]+}', \App\Modules\Controllers\EjercicioController::class . ':editar');
            $adminGroup->delete('/{id:[0-9]+}', \App\Modules\Controllers\EjercicioController::class . ':eliminar');
            $adminGroup->post('/bulk', \App\Modules\Controllers\EjercicioController::class . ':importarBulk');
        })->add(AdminMiddleware::class);
    })->add(TokenBlacklistMiddleware::class)
        ->add(AuthMiddleware::class);

    // --- RESPUESTAS DE EJERCICIO ---
    $app->group('/respuestas-ejercicio', function (RouteCollectorProxy $group) {
        $group->get('', \App\Modules\Controllers\RespuestaEjercicioController::class . ':listar');
        $group->get('/{id:[0-9]+}', \App\Modules\Controllers\RespuestaEjercicioController::class . ':ver');

        $group->group('', function (RouteCollectorProxy $adminGroup) {
            $adminGroup->post('', \App\Modules\Controllers\RespuestaEjercicioController::class . ':crear');
            $adminGroup->put('/{id:[0-9]+}', \App\Modules\Controllers\RespuestaEjercicioController::class . ':editar');
            $adminGroup->delete('/{id:[0-9]+}', \App\Modules\Controllers\RespuestaEjercicioController::class . ':eliminar');
        })->add(AdminMiddleware::class);
    })->add(TokenBlacklistMiddleware::class)
        ->add(AuthMiddleware::class);

    // Rutas de Usuarios (Protegidas)
    $app->group('/usuarios', function (RouteCollectorProxy $group) {
        $group->get('/perfil', \App\Modules\Controllers\UsuarioController::class . ':getPerfil');
        $group->put('/perfil', \App\Modules\Controllers\UsuarioController::class . ':updateProfile');
        $group->get('/profile/{username}', \App\Modules\Controllers\UsuarioController::class . ':getPublicProfile');
        $group->get('/suscripcion', \App\Modules\Controllers\UsuarioController::class . ':getSuscripcion');
        $group->post('/suscripcion', \App\Modules\Controllers\UsuarioController::class . ':suscribirse');
        $group->post('/suscripcion/cancelar', \App\Modules\Controllers\UsuarioController::class . ':cancelarSuscripcion');
        $group->post('/imagen', \App\Modules\Controllers\UsuarioController::class . ':updateImagen');
        $group->get('/estadisticas', \App\Modules\Controllers\UsuarioController::class . ':getEstadisticas');
        $group->put('/password', \App\Modules\Controllers\UsuarioController::class . ':updatePassword');

        $group->group('', function (RouteCollectorProxy $adminGroup) {
            $adminGroup->get('/buscar', \App\Modules\Controllers\UsuarioController::class . ':buscar');
            $adminGroup->post('/update-role', \App\Modules\Controllers\UsuarioController::class . ':updateRole');
            $adminGroup->put('/reset-password', \App\Modules\Controllers\UsuarioController::class . ':adminResetPassword');
        })->add(AdminMiddleware::class);
    })->add(TokenBlacklistMiddleware::class)
        ->add(AuthMiddleware::class);

    // Rutas de Planes de Suscripción - Todo administrativo excepto listar disponibles
    $app->get('/planes-public', \App\Modules\Controllers\PlanSuscripcionController::class . ':getPlanesDisponibles');

    $app->group('/planes', function (RouteCollectorProxy $group) {
        $group->get('', \App\Modules\Controllers\PlanSuscripcionController::class . ':getPlanesDisponibles');

        $group->group('', function (RouteCollectorProxy $adminGroup) {
            $adminGroup->get('/{id:[0-9]+}', \App\Modules\Controllers\PlanSuscripcionController::class . ':get');
            $adminGroup->post('', \App\Modules\Controllers\PlanSuscripcionController::class . ':create');
            $adminGroup->put('/{id:[0-9]+}', \App\Modules\Controllers\PlanSuscripcionController::class . ':update');
            $adminGroup->delete('/{id:[0-9]+}', \App\Modules\Controllers\PlanSuscripcionController::class . ':delete');
        })->add(AdminMiddleware::class);
    })->add(TokenBlacklistMiddleware::class)
        ->add(AuthMiddleware::class);

    // Rutas de Suscripciones (Solo admin)
    $app->group('/suscripciones', function (RouteCollectorProxy $group) {
        $group->get('', \App\Modules\Controllers\SuscripcionController::class . ':listar');
        $group->get('/{id:[0-9]+}', \App\Modules\Controllers\SuscripcionController::class . ':ver');
    })->add(AdminMiddleware::class)
        ->add(TokenBlacklistMiddleware::class)
        ->add(AuthMiddleware::class);

    // Rutas de difusión
    $app->group('/difusion', function (RouteCollectorProxy $group) {
        $group->post('/send', \App\Modules\Controllers\DifusionController::class . ':sendBroadcast');
    })->add(AdminMiddleware::class)
        ->add(TokenBlacklistMiddleware::class)
        ->add(AuthMiddleware::class);


    // Ruta de prueba
    $app->get('/health', function ($request, $response) {
        $response->getBody()->write('OK');
        return $response;
    });
};
