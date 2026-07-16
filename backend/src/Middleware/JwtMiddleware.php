<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class JwtMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $payload = $this->extractPayload($request);
        if ($payload !== null) {
            $request = $request->withAttribute('jwtPayload', $payload);
        }

        return $handler->handle($request);
    }

    private function extractPayload(ServerRequestInterface $request): ?array
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

        $expected = hash_hmac('sha256', $payload, $this->secretKey());
        if (!hash_equals($expected, $signature)) {
            return null;
        }

        $decoded = json_decode(base64_decode($payload), true);
        if (!is_array($decoded)) {
            return null;
        }

        return $decoded;
    }

    private function secretKey(): string
    {
        return (string)env('JWT_KEY', 'dev-secret-change-me');
    }
}
