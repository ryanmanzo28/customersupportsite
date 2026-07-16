<?php

declare(strict_types=1);

use Migrations\AbstractMigration;

class InitialSchema extends AbstractMigration
{
    public function change(): void
    {
        $this->table('users')
            ->addColumn('username', 'string', ['limit' => 255])
            ->addColumn('password', 'string', ['limit' => 255])
            ->addColumn('created', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('modified', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['username'], ['unique' => true])
            ->create();

        $this->table('tickets')
            ->addColumn('submitting_user_id', 'integer', ['signed' => false])
            ->addColumn('status', 'string', ['limit' => 50, 'default' => 'open'])
            ->addColumn('title', 'string', ['limit' => 255])
            ->addColumn('body', 'text')
            ->addColumn('created', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('modified', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey(['submitting_user_id'], 'users', ['id'], [
                'constraint' => 'fk_tickets_users',
                'delete' => 'RESTRICT',
                'update' => 'CASCADE',
            ])
            ->create();

        $this->table('comments')
            ->addColumn('ticket_id', 'integer', ['signed' => false])
            ->addColumn('user_id', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('commenter_name', 'string', ['limit' => 255])
            ->addColumn('comment_body', 'text')
            ->addColumn('created', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('modified', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey(['ticket_id'], 'tickets', ['id'], [
                'constraint' => 'fk_comments_tickets',
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ])
            ->addForeignKey(['user_id'], 'users', ['id'], [
                'constraint' => 'fk_comments_users',
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
            ])
            ->create();

        $this->execute(
            'INSERT INTO users (username, password, created, modified)
            SELECT "demo", "$2y$10$u8FE/qvZ9/q.dY5HJvK9YuEDhEQHI0t1VxT4/JkN2lJ8qV1Gxu3VW", NOW(), NOW()
            WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = "demo")'
        );
    }
}
