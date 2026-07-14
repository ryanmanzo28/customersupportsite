<h1>Ticket #<?= h($ticket->id) ?></h1>

<p><strong>Title:</strong> <?= h($ticket->title) ?></p>
<p><strong>Status:</strong> <?= h($ticket->status) ?></p>
<p><strong>Submitting User:</strong> <?= h($ticket->user->username ?? $ticket->submitting_user_id) ?></p>
<p><strong>Body:</strong></p>
<p><?= nl2br(h($ticket->body)) ?></p>

<p><?= $this->Html->link('Back to Tickets', ['action' => 'index']) ?></p>
