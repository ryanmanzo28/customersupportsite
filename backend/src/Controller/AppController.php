<?php

declare(strict_types=1);

namespace App\Controller;

use Cake\Controller\Controller;

class AppController extends Controller
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('RequestHandler');
    }

    protected function issueAuthToken(int $id, string $username): string
    {
        $payload = base64_encode(json_encode([
            'id' => $id,
            'username' => $username,
            'iat' => time(),
        ], JSON_UNESCAPED_SLASHES));

        $signature = hash_hmac('sha256', $payload, $this->authSecret());

        return $payload . '.' . $signature;
    }

    protected function authenticatedUser(): ?array
    {
        $header = (string)$this->request->getHeaderLine('Authorization');
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
