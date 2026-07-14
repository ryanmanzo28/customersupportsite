<?php

class UsersTable extends Table
{
    public function __construct(array $options = [])
{
    parent::__construct($options);

    $this->setTable('users');
    $this->setDisplayField('id');
    $this->setPrimaryKey('id');

    $this->addBehavior('');
}

public function validationDefault(Validator $validator)
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