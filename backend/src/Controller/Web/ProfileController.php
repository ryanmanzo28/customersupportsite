<?php

declare(strict_types=1);

namespace App\Controller\Web;

use App\Controller\AppController;

class ProfileController extends AppController
{
    public function index()
    {
        $user = $this->authenticatedUser();
        if (!$user) {
            $this->Flash->error('Please log in to view your profile.');
            return $this->redirect(['controller' => 'Auth', 'action' => 'login']);
        }

        $this->set(compact('user'));
    }
}