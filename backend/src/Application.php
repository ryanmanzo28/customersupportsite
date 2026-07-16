<?php

declare(strict_types=1);

namespace App;

use App\Middleware\AuthMiddleware;
use App\Middleware\CorsMiddleware;
use Cake\Core\Configure;
use Cake\Core\ContainerInterface;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\BaseApplication;
use Cake\Http\Middleware\BodyParserMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Middleware\RoutingMiddleware;

class Application extends BaseApplication
{
    public function bootstrap(): void
    {
        parent::bootstrap();

        if (class_exists(\Authentication\Plugin::class)) {
            $this->addPlugin('Authentication');
        }

        if (class_exists(\Authorization\Plugin::class)) {
            $this->addPlugin('Authorization');
        }

    }

    public function routes(RouteBuilder $routes): void
    {
        $loader = require dirname(__DIR__) . '/config/routes.php';
        if ($loader instanceof \Closure) {
            $loader($routes);
        }
    }
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        $middlewareQueue
            ->add(new CorsMiddleware())
            ->add(new ErrorHandlerMiddleware([
                'debug' => (bool)Configure::read('debug', false),
            ]))
            ->add(new RoutingMiddleware($this))
            ->add(new BodyParserMiddleware())
            ->add(new AuthMiddleware());

        return $middlewareQueue;
    }

    public function services(ContainerInterface $container): void
    {
    }
}
