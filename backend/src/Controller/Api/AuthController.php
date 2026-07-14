<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController;

class AuthController extends AppController
{
    public function login()
    {
        $username = (string)($this->request->getData('username') ?? '');
        $password = (string)($this->request->getData('password') ?? '');

        if ($username === '' || $password === '') {
            $this->response = $this->response->withStatus(422);
            $this->set([
                'success' => false,
                'message' => 'Username and password are required',
                '_serialize' => ['success', 'message'],
            ]);
            return;
        }

        $user = $this->fetchTable('Users')
            ->find()
            ->select(['id', 'username', 'password'])
            ->where(['username' => $username])
            ->first();

        if (!$user || $user->password !== $password) {
            $this->response = $this->response->withStatus(401);
            $this->set([
                'success' => false,
                'message' => 'Invalid credentials',
                '_serialize' => ['success', 'message'],
            ]);
            return;
        }

        $token = $this->issueAuthToken((int)$user->id, (string)$user->username);

        $this->set([
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => (int)$user->id,
                    'username' => (string)$user->username,
                ],
            ],
            '_serialize' => ['success', 'data'],
        ]);
    }

    public function me()
    {
        $authUser = $this->authenticatedUser();
        if (!$authUser) {
            $this->response = $this->response->withStatus(401);
            $this->set([
                'success' => false,
                'message' => 'Unauthorized',
                '_serialize' => ['success', 'message'],
            ]);
            return;
        }

        $this->set([
            'success' => true,
            'data' => $authUser,
            '_serialize' => ['success', 'data'],
        ]);
    }
}
