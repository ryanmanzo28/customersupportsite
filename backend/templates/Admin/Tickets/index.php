<h1>Admin Tickets</h1>

<?php if (empty($tickets->toList())): ?>
<p>No tickets found.</p>
<?php else: ?>
<table>
	<thead>
		<tr>
			<th>ID</th>
			<th>Title</th>
			<th>Status</th>
			<th>User</th>
			<th>Action</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($tickets as $ticket): ?>
		<tr>
			<td><?= h($ticket->id) ?></td>
			<td><?= h($ticket->title) ?></td>
			<td><?= h($ticket->status) ?></td>
			<td><?= h($ticket->user->username ?? $ticket->submitting_user_id) ?></td>
			<td><?= $this->Html->link('View', ['action' => 'view', $ticket->id]) ?></td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?php endif; ?>
