<?php

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Nyholm\Psr7\Factory\Psr17Factory;

return [
    'settings' => function () {
        return require __DIR__ . '/settings.php';
    },

        // Auto-inyección del Container - Permite que los controladores reciban el container
    ContainerInterface::class => function (ContainerInterface $container) {
        return $container;
    },

        // PSR-7 Factories - Necesarias para Slim y Middlewares
    ResponseFactoryInterface::class => function (ContainerInterface $container) {
        return new Psr17Factory();
    },

    ServerRequestFactoryInterface::class => function (ContainerInterface $container) {
        return new Psr17Factory();
    },

    StreamFactoryInterface::class => function (ContainerInterface $container) {
        return new Psr17Factory();
    },

    UploadedFileFactoryInterface::class => function (ContainerInterface $container) {
        return new Psr17Factory();
    },

        // Slim App
    App::class => function (ContainerInterface $container) {
        AppFactory::setContainer($container);

        return AppFactory::create();
    },
];