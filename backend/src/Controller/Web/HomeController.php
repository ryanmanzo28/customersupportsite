<?php

declare(strict_types=1);

namespace App\Controller\Web;

use App\Controller\AppController;

class HomeController extends AppController
{
	public function index()
	{
		$recentTickets = $this->fetchTable('Tickets')
			->find()
			->select(['id', 'title', 'status', 'created'])
			->orderByDesc('id')
			->limit(10)
			->all();

		$this->set(compact('recentTickets'));
	}
}
