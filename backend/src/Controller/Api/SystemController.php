<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController;

class SystemController extends AppController
{
    public function health()
    {
        $dbOk = true;
        $dbError = null;

        try {
            $this->fetchTable('Users')->find()->select(['id'])->limit(1)->first();
        } catch (\Throwable $exception) {
            $dbOk = false;
            $dbError = $exception->getMessage();
        }

        $status = $dbOk ? 'ok' : 'degraded';
        $httpCode = $dbOk ? 200 : 503;

        $this->response = $this->response->withStatus($httpCode);
        $this->set([
            'success' => $dbOk,
            'status' => $status,
            'time' => gmdate('c'),
            'checks' => [
                'database' => [
                    'ok' => $dbOk,
                    'error' => $dbError,
                ],
            ],
            '_serialize' => ['success', 'status', 'time', 'checks'],
        ]);
    }

    public function stats()
    {
        $ticketsTable = $this->fetchTable('Tickets');
        $commentsTable = $this->fetchTable('Comments');

        $this->set([
            'success' => true,
            'data' => [
                'tickets' => [
                    'total' => $ticketsTable->find()->count(),
                    'open' => $ticketsTable->find()->where(['status' => 'open'])->count(),
                    'in_progress' => $ticketsTable->find()->where(['status' => 'in_progress'])->count(),
                    'closed' => $ticketsTable->find()->where(['status' => 'closed'])->count(),
                ],
                'comments' => [
                    'total' => $commentsTable->find()->count(),
                ],
            ],
            '_serialize' => ['success', 'data'],
        ]);
    }
}
