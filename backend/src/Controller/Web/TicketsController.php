<?php

declare(strict_types=1);

namespace App\Controller\Web;

use App\Controller\AppController;

class TicketsController extends AppController
{
    public function index()
    {
        $tickets = $this->fetchTable('Tickets')
            ->find()
            ->contain(['Users'])
            ->orderByDesc('Tickets.id')
            ->limit(100)
            ->all();

        $this->set(compact('tickets'));
    }

    public function view(int $id)
    {
        $ticket = $this->fetchTable('Tickets')
            ->find()
            ->contain(['Users', 'Comments'])
            ->where(['Tickets.id' => $id])
            ->firstOrFail();

        $this->set(compact('ticket'));
    }
}