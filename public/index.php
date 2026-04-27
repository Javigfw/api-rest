<?php

use App\Helpers\Utils;

// header('Access-Control-Allow-Origin: https://testhorario.intersoftalmeria.es');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept, Origin, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=UTF-8');

// Manejar solicitudes OPTIONS (preflight CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

//error_reporting(0);

require_once __DIR__ . '/../vendor/autoload.php';

$containerBuilder = new DI\ContainerBuilder();

// Add DI container definitions
$containerBuilder->addDefinitions(__DIR__ . '/../app/container.php');

// Create DI container instance
$container = $containerBuilder->build();

$settings =  $container->get('settings');

// Create Slim App instance
$app = $container->get(Slim\App::class);

require_once __DIR__ . '/../app/config.php';

// Detección automática del Base Path (necesario para Slim 4 en subcarpetas)
$basePath = defined('API_BASE_PATH') ? API_BASE_PATH : '';
if (empty($basePath)) {
    // Si no está definido en config.php, detectarlo dinámicamente
    $basePath = str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']);
}
$app->setBasePath($basePath);

// Register routes
(require __DIR__ . '/../app/routes.php')($app);

// Register middleware
(require __DIR__ . '/../app/middleware.php')($app);


$app->run();
