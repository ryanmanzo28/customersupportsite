<?php

declare(strict_types=1);

namespace App\Model\Table;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
class CommentsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('comments');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Tickets', [
            'foreignKey' => 'ticket_id',
            'joinType' => 'INNER',
        ]);

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'LEFT',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->integer('ticket_id')
            ->requirePresence('ticket_id', 'create')
            ->notEmptyString('ticket_id');

        $validator
            ->integer('user_id')
            ->allowEmptyString('user_id');

        $validator
            ->scalar('commenter_name')
            ->maxLength('commenter_name', 255)
            ->requirePresence('commenter_name', 'create')
            ->notEmptyString('commenter_name');

        $validator
            ->scalar('comment_body')
            ->requirePresence('comment_body', 'create')
            ->notEmptyString('comment_body');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['ticket_id'], 'Tickets'), ['errorField' => 'ticket_id']);

        return $rules;
    }
}
