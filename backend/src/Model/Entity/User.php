<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class User extends Entity
{
    protected array $_accessible = [
        'id' => true,
        'username' => true,
        'password' => true,
        'created' => true,
        'modified' => true,
        'tickets' => true,
    ];
}