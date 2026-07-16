<?php

declare(strict_types=1);

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes): void {
    $routes->setRouteClass(DashedRoute::class);
    $routes->setExtensions(['json']);

    $routes->connect('/', ['prefix' => 'Api', 'controller' => 'Tickets', 'action' => 'index']);

    $routes->prefix('Api', function (RouteBuilder $api): void {
        $api->connect('/health', ['controller' => 'System', 'action' => 'health'])->setMethods(['GET']);
        $api->connect('/stats', ['controller' => 'System', 'action' => 'stats'])->setMethods(['GET']);

        $api->connect('/auth/login', ['controller' => 'Auth', 'action' => 'login'])->setMethods(['POST']);
        $api->connect('/auth/register', ['controller' => 'Auth', 'action' => 'register'])->setMethods(['POST']);
        $api->connect('/auth/me', ['controller' => 'Auth', 'action' => 'me'])->setMethods(['GET']);

        $api->connect('/tickets', ['controller' => 'Tickets', 'action' => 'index'])->setMethods(['GET']);
        $api->connect('/tickets', ['controller' => 'Tickets', 'action' => 'add'])->setMethods(['POST']);
        $api->connect('/tickets/{id}', ['controller' => 'Tickets', 'action' => 'view'])
            ->setMethods(['GET'])
            ->setPatterns(['id' => '\\d+'])
            ->setPass(['id']);
        $api->connect('/tickets/{id}/status', ['controller' => 'Tickets', 'action' => 'updateStatus'])
            ->setMethods(['PATCH'])
            ->setPatterns(['id' => '\\d+'])
            ->setPass(['id']);
        $api->connect('/tickets/{id}/comments', ['controller' => 'Tickets', 'action' => 'addComment'])
            ->setMethods(['POST'])
            ->setPatterns(['id' => '\\d+'])
            ->setPass(['id']);
    });

    $routes->fallbacks(DashedRoute::class);
};
