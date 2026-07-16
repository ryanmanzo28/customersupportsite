<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class UsersTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('users');
        $this->setDisplayField('username');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
    }

    public function validationDefault(Validator $validator): Validator
{
    $validator
        ->integer('id')
        ->allowEmptyString('id', null, 'create');

    $validator
        ->scalar('username')
        ->maxLength('username', 255)
        ->requirePresence('username', 'create')
        ->notEmptyString('username');

    $validator
        ->scalar('password')
        ->maxLength('password', 255)
        ->requirePresence('password', 'create')
        ->notEmptyString('password');

    return $validator;
}
}