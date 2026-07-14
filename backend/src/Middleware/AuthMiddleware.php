<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = $this->extractUser($request);
        if ($user) {
            $request = $request->withAttribute('authUser', $user);
        }

        return $handler->handle($request);
    }

    private function extractUser(ServerRequestInterface $request): ?array
    {
        $header = $request->getHeaderLine('Authorization');
        if (!str_starts_with($header, 'Bearer ')) {
            return null;
        }

        $token = trim(substr($header, 7));
        [$payload, $signature] = array_pad(explode('.', $token, 2), 2, null);

        if (!$payload || !$signature) {
            return null;
        }

        $expected = hash_hmac('sha256', $payload, $this->authSecret());
        if (!hash_equals($expected, $signature)) {
            return null;
        }

        $decoded = json_decode(base64_decode($payload), true);
        if (!is_array($decoded) || empty($decoded['id']) || empty($decoded['username'])) {
            return null;
        }

        return [
            'id' => (int)$decoded['id'],
            'username' => (string)$decoded['username'],
        ];
    }

    private function authSecret(): string
    {
        return (string)env('APP_KEY', 'dev-secret-change-me');
    }
}
