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
use Cake\Routing\Middleware\RoutingMiddleware;

class Application extends BaseApplication
{
    public function bootstrap(): void
    {
        parent::bootstrap();

        require $this->getConfigDir() . '/routes.php';
    }

    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        $middlewareQueue
            ->add(new CorsMiddleware())
            ->add(new ErrorHandlerMiddleware(Configure::read('Error')))
            ->add(new BodyParserMiddleware())
            ->add(new AuthMiddleware())
            ->add(new RoutingMiddleware($this));

        return $middlewareQueue;
    }

    public function services(ContainerInterface $container): void
    {
    }
}
