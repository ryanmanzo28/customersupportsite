<?php

declare(strict_types=1);

namespace App\Controller\Web;

use App\Controller\AppController;

class AuthController extends AppController
{
    public function login()
    {
        // GET renders form, POST processes submission
        if ($this->request->is('post')) {
            $username = (string)($this->request->getData('username') ?? '');
            $password = (string)($this->request->getData('password') ?? '');

            if ($username === '' || $password === '') {
                $this->Flash->error('Username and password are required');
                return;
            }

            $user = $this->fetchTable('Users')
                ->find()
                ->select(['id', 'username', 'password'])
                ->where(['username' => $username])
                ->first();

            if (!$user || !password_verify($password, (string)$user->password)) {
                $this->Flash->error('Invalid credentials');
                return;
            }

            $this->Flash->success('Login successful');
            return $this->redirect('/');
        }
    }

    public function register()
    {
        // GET renders form, POST processes submission
        if ($this->request->is('post')) {
            $username = (string)($this->request->getData('username') ?? '');
            $password = (string)($this->request->getData('password') ?? '');
            $passwordConfirm = (string)($this->request->getData('password_confirm') ?? '');

            if ($username === '' || $password === '') {
                $this->Flash->error('Username and password are required');
                return;
            }

            if ($password !== $passwordConfirm) {
                $this->Flash->error('Passwords do not match');
                return;
            }

            $usersTable = $this->fetchTable('Users');
            $existing = $usersTable->find()->where(['username' => $username])->first();

            if ($existing) {
                $this->Flash->error('Username already taken');
                return;
            }

            $user = $usersTable->newEmptyEntity();
            $user = $usersTable->patchEntity($user, [
                'username' => $username,
                'password' => password_hash($password, PASSWORD_DEFAULT),
            ]);

            if (!$usersTable->save($user)) {
                $this->Flash->error('Registration failed');
                return;
            }

            $this->Flash->success('Account created successfully. Please log in.');
            return $this->redirect(['action' => 'login']);
        }
    }

    public function logout()
    {
        $this->Flash->success('Logged out successfully');
        return $this->redirect('/');
    }

    public function checkAdmin(int $id): bool
    {
        $user = $this->fetchTable('Users')->find()->where(['id' => $id])->first();
        if ($user === null) {
            return false;
        }

        if (!isset($user->admin)) {
            return false;
        }

        return (bool)$user->admin;
    }
}