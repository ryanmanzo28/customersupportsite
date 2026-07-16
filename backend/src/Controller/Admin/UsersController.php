<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;

class UsersController extends AppController
{
    public function index()
    {
        $users = $this->fetchTable('Users')
            ->find()
            ->select(['id', 'username', 'created', 'modified'])
            ->orderByDesc('id')
            ->limit(200)
            ->all();

        $this->set(compact('users'));
    }
}



