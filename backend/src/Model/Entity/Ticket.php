<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Ticket extends Entity
{
    protected array $_accessible = [
        'submitting_user_id' => true,
        'status' => true,
        'title' => true,
        'body' => true,
        'created' => true,
        'modified' => true,
        'user' => true,
    ];
}
