<?php

declare(strict_types=1);

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;

Router::defaultRouteClass(DashedRoute::class);

Router::scope('/', function (RouteBuilder $routes): void {
    $routes->setExtensions(['json']);

    $routes->connect('/', ['prefix' => 'Api', 'controller' => 'Tickets', 'action' => 'index']);

    $routes->prefix('Api', function (RouteBuilder $api): void {
        $api->connect('/auth/login', ['controller' => 'Auth', 'action' => 'login', '_method' => 'POST']);
        $api->connect('/auth/register', ['controller' => 'Auth', 'action' => 'register', '_method' => 'POST']);
        $api->connect('/auth/me', ['controller' => 'Auth', 'action' => 'me', '_method' => 'GET']);

        $api->connect('/tickets', ['controller' => 'Tickets', 'action' => 'index', '_method' => 'GET']);
        $api->connect('/tickets', ['controller' => 'Tickets', 'action' => 'add', '_method' => 'POST']);
        $api->connect('/tickets/{id}', ['controller' => 'Tickets', 'action' => 'view', '_method' => 'GET'])
            ->setPatterns(['id' => '\\d+'])
            ->setPass(['id']);
        $api->connect('/tickets/{id}/status', ['controller' => 'Tickets', 'action' => 'updateStatus', '_method' => 'PATCH'])
            ->setPatterns(['id' => '\\d+'])
            ->setPass(['id']);
        $api->connect('/tickets/{id}/comments', ['controller' => 'Tickets', 'action' => 'addComment', '_method' => 'POST'])
            ->setPatterns(['id' => '\\d+'])
            ->setPass(['id']);
    });

    $routes->fallbacks();
});
