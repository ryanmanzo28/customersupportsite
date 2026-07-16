<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Category extends Entity
{
    protected array $_accessible = [
        'id' => true,
        'name' => true,
        'created' => true,
        'modified' => true,
    ];
}