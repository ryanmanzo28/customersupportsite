<?php

declare(strict_types=1);

namespace App\Middleware;

use Cake\Core\App;
use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RouteDebugMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $query = $request->getQueryParams();
        if (($query['debugRoute'] ?? null) !== '1') {
            return $handler->handle($request);
        }

        $params = [
            'prefix' => $request->getAttribute('params')['prefix'] ?? null,
            'controller' => $request->getAttribute('params')['controller'] ?? null,
            'action' => $request->getAttribute('params')['action'] ?? null,
            'plugin' => $request->getAttribute('params')['plugin'] ?? null,
            '_ext' => $request->getAttribute('params')['_ext'] ?? null,
            'fullParams' => $request->getAttribute('params'),
        ];

        if (($query['debugRoute'] ?? null) === '2') {
            $namespace = 'Controller';
            $prefix = $params['prefix'];
            if (is_string($prefix) && $prefix !== '') {
                $namespace .= '/' . $prefix;
            }

            $controller = (string)($params['controller'] ?? '');
            $className = App::className($controller, $namespace, 'Controller');
            $params['resolvedClass'] = $className;
            $params['resolvedClassExists'] = $className ? class_exists($className) : false;
        }

        $response = new Response();

        return $response
            ->withType('application/json')
            ->withStringBody((string)json_encode($params, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
