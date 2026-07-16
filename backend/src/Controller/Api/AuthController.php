<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController;

class AuthController extends AppController
{
    public function login()
    {
        $username = trim((string)($this->request->getData('username') ?? ''));
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

        if (!$user || !password_verify($password, (string)$user->password)) {
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

    public function register()
    {
        $username = trim((string)($this->request->getData('username') ?? ''));
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

        if (strlen($password) < 8) {
            $this->response = $this->response->withStatus(422);
            $this->set([
                'success' => false,
                'message' => 'Password must be at least 8 characters',
                '_serialize' => ['success', 'message'],
            ]);
            return;
        }

        $usersTable = $this->fetchTable('Users');
        $existing = $usersTable->find()->where(['username' => $username])->first();

        if ($existing) {
            $this->response = $this->response->withStatus(422);
            $this->set([
                'success' => false,
                'message' => 'Username already taken',
                '_serialize' => ['success', 'message'],
            ]);
            return;
        }

        $user = $usersTable->newEmptyEntity();
        $user = $usersTable->patchEntity($user, [
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        if (!$usersTable->save($user)) {
            $this->response = $this->response->withStatus(422);
            $this->set([
                'success' => false,
                'errors' => $user->getErrors(),
                '_serialize' => ['success', 'errors'],
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
}
