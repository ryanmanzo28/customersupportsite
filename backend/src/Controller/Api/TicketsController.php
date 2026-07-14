<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController;

class TicketsController extends AppController
{
    public function index()
    {
        $tickets = $this->fetchTable('Tickets')
            ->find()
            ->select(['id', 'submitting_user_id', 'status', 'title', 'body', 'created'])
            ->orderByDesc('id')
            ->limit(50)
            ->all()
            ->toList();

        $this->set([
            'success' => true,
            'data' => $tickets,
            '_serialize' => ['success', 'data'],
        ]);
    }

    public function add()
    {
        $ticketsTable = $this->fetchTable('Tickets');
        $ticket = $ticketsTable->newEmptyEntity();

        $payload = (array)$this->request->getData();
        $payload['status'] = $payload['status'] ?? 'open';

        $ticket = $ticketsTable->patchEntity($ticket, $payload);

        if ($ticketsTable->save($ticket)) {
            $this->set([
                'success' => true,
                'data' => $ticket,
                '_serialize' => ['success', 'data'],
            ]);
            return;
        }

        $this->response = $this->response->withStatus(422);
        $this->set([
            'success' => false,
            'errors' => $ticket->getErrors(),
            '_serialize' => ['success', 'errors'],
        ]);
    }

    public function view(int $id)
    {
        $ticket = $this->fetchTable('Tickets')
            ->find()
            ->select(['id', 'submitting_user_id', 'status', 'title', 'body', 'created', 'modified'])
            ->contain(['Comments' => function ($query) {
                return $query->orderBy(['Comments.id' => 'ASC']);
            }])
            ->where(['id' => $id])
            ->first();

        if (!$ticket) {
            $this->response = $this->response->withStatus(404);
            $this->set([
                'success' => false,
                'message' => 'Ticket not found',
                '_serialize' => ['success', 'message'],
            ]);
            return;
        }

        $this->set([
            'success' => true,
            'data' => $ticket,
            '_serialize' => ['success', 'data'],
        ]);
    }

    public function addComment(int $id)
    {
        $ticketsTable = $this->fetchTable('Tickets');
        $ticket = $ticketsTable->get($id);

        $commentsTable = $this->fetchTable('Comments');
        $comment = $commentsTable->newEmptyEntity();

        $payload = [
            'ticket_id' => $ticket->id,
            'user_id' => $this->request->getData('user_id') ?: null,
            'commenter_name' => (string)($this->request->getData('commenter_name') ?? ''),
            'comment_body' => (string)($this->request->getData('comment_body') ?? ''),
        ];

        $comment = $commentsTable->patchEntity($comment, $payload);

        if (!$commentsTable->save($comment)) {
            $this->response = $this->response->withStatus(422);
            $this->set([
                'success' => false,
                'errors' => $comment->getErrors(),
                '_serialize' => ['success', 'errors'],
            ]);
            return;
        }

        $this->set([
            'success' => true,
            'data' => $comment,
            '_serialize' => ['success', 'data'],
        ]);
    }

    public function updateStatus(int $id)
    {
        $ticketsTable = $this->fetchTable('Tickets');
        $ticket = $ticketsTable->get($id);

        $status = (string)($this->request->getData('status') ?? '');
        if ($status === '') {
            $this->response = $this->response->withStatus(422);
            $this->set([
                'success' => false,
                'message' => 'Status is required',
                '_serialize' => ['success', 'message'],
            ]);
            return;
        }

        $ticket = $ticketsTable->patchEntity($ticket, ['status' => $status], ['fields' => ['status']]);
        if (!$ticketsTable->save($ticket)) {
            $this->response = $this->response->withStatus(422);
            $this->set([
                'success' => false,
                'errors' => $ticket->getErrors(),
                '_serialize' => ['success', 'errors'],
            ]);
            return;
        }

        $this->set([
            'success' => true,
            'data' => $ticket,
            '_serialize' => ['success', 'data'],
        ]);
    }
}
