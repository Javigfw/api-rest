<?php

use Slim\App;

require_once __DIR__.'/config.php';

return function (App $app) {
    // Parse json, form data and xml
    $app->addBodyParsingMiddleware();

    // Add the Slim built-in routing middleware
    $app->addRoutingMiddleware();

    // Handle exceptions
    $app->addErrorMiddleware(MIDDLEWARE_DISPLAY_ERROR_DETAILS, MIDDLEWARE_LOG_ERROR, MIDDLEWARE_LOG_ERROR);
};