<?php

declare(strict_types=1);

namespace App\Middleware;

use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CorsMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $allowOrigin = $this->resolveAllowOrigin($request);
        $response = strtoupper($request->getMethod()) === 'OPTIONS'
            ? (new Response())->withStatus(204)
            : $handler->handle($request);

        if ($allowOrigin !== '') {
            $response = $response->withHeader('Access-Control-Allow-Origin', $allowOrigin);
        }

        if ($allowOrigin !== '*') {
            $response = $response->withHeader('Vary', 'Origin');
        }

        return $response
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
            ->withHeader('Access-Control-Max-Age', '3600');
    }

    private function resolveAllowOrigin(ServerRequestInterface $request): string
    {
        $allowedOrigins = $this->allowedOrigins();
        if (in_array('*', $allowedOrigins, true)) {
            return '*';
        }

        $origin = trim($request->getHeaderLine('Origin'));
        if ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
            return $origin;
        }

        return '';
    }

    private function allowedOrigins(): array
    {
        $configured = (string)env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173,http://127.0.0.1:5173');
        $origins = array_values(array_filter(array_map('trim', explode(',', $configured)), static function (string $value): bool {
            return $value !== '';
        }));

        return $origins !== [] ? $origins : ['*'];
    }
}
