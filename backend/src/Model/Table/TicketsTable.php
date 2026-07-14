<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class TicketsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('tickets');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Users', [
            'foreignKey' => 'submitting_user_id',
            'joinType' => 'INNER',
        ]);

        $this->hasMany('Comments', [
            'foreignKey' => 'ticket_id',
            'dependent' => true,
            'cascadeCallbacks' => true,
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->integer('submitting_user_id')
            ->requirePresence('submitting_user_id', 'create')
            ->notEmptyString('submitting_user_id');

        $validator
            ->scalar('status')
            ->maxLength('status', 50)
            ->inList('status', ['open', 'in_progress', 'closed'])
            ->requirePresence('status', 'create')
            ->notEmptyString('status');

        $validator
            ->scalar('title')
            ->maxLength('title', 255)
            ->requirePresence('title', 'create')
            ->notEmptyString('title');

        $validator
            ->scalar('body')
            ->requirePresence('body', 'create')
            ->notEmptyString('body');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['submitting_user_id'], 'Users'));

        return $rules;
    }
}
