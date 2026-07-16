<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;

class DashboardController extends AppController
{
    public function index()
    {
        $ticketsTable = $this->fetchTable('Tickets');
        $usersTable = $this->fetchTable('Users');

        $stats = [
            'ticketsTotal' => $ticketsTable->find()->count(),
            'ticketsOpen' => $ticketsTable->find()->where(['status' => 'open'])->count(),
            'ticketsClosed' => $ticketsTable->find()->where(['status' => 'closed'])->count(),
            'usersTotal' => $usersTable->find()->count(),
        ];

        $this->set(compact('stats'));
    }
}
