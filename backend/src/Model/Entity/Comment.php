<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Comment extends Entity
{
    protected array $_accessible = [
        'ticket_id' => true,
        'user_id' => true,
        'commenter_name' => true,
        'comment_body' => true,
        'created' => true,
        'modified' => true,
        'ticket' => true,
        'user' => true,
    ];
}
