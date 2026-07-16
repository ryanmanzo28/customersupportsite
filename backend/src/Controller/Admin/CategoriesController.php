<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;

class CategoriesController extends AppController
{
    public function index()
    {
        $this->set('categories', []);
    }
}
