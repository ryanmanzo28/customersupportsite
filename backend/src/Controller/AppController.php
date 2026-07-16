<?php

declare(strict_types=1);

namespace App\Controller;

use Cake\Controller\Controller;
use Cake\View\JsonView;

class AppController extends Controller
{
    public function initialize(): void
    {
        parent::initialize();

        if (strcasecmp((string)$this->request->getParam('prefix'), 'Api') === 0) {
            $this->viewBuilder()->setClassName(JsonView::class);
            $this->viewBuilder()->setOption('serialize', true);
        }
    }

    protected function issueAuthToken(int $id, string $username): string
    {
        $issuedAt = time();
        $expiresAt = $issuedAt + $this->authTokenTtl();

        $payload = base64_encode(json_encode([
            'id' => $id,
            'username' => $username,
            'iat' => $issuedAt,
            'exp' => $expiresAt,
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

        $decoded = json_decode(base64_decode($payload, true), true);
        if (!is_array($decoded) || empty($decoded['id']) || empty($decoded['username'])) {
            return null;
        }

        $expiresAt = (int)($decoded['exp'] ?? 0);
        if ($expiresAt > 0 && $expiresAt < time()) {
            return null;
        }

        return [
            'id' => (int)$decoded['id'],
            'username' => (string)$decoded['username'],
        ];
    }

    private function authTokenTtl(): int
    {
        return max(300, (int)env('AUTH_TOKEN_TTL', '604800'));
    }

    private function authSecret(): string
    {
        return (string)env('APP_KEY', 'dev-secret-change-me');
    }
}
