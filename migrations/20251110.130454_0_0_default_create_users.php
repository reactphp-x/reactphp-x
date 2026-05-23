<?php

declare(strict_types=1);

namespace Migration;

use Cycle\Migrations\Migration;

class CreateUsers extends Migration
{
    protected const DATABASE = 'default';

    public function up(): void
    {
        $this->table('users')
        ->addColumn('id', 'bigPrimary', [
            'autoIncrement' => true,
            'nullable' => false,
            'unsigned' => true,
        ])
        ->addColumn('name', 'string', [
            'nullable' => false,
            'defaultValue' => null,
            'size' => 255,
            'comment' => '',
        ])
        ->addColumn('email', 'string', [
            'nullable' => false,
            'defaultValue' => null,
            'size' => 255,
            'comment' => '',
        ])
        ->addColumn('status', 'smallInteger', [
            'nullable' => false,
            'defaultValue' => 0,
            'size' => 4,
            'comment' => '默认0: 未激活, 1:正常',
        ])
        ->addColumn('avatar', 'string', [
            'nullable' => true,
            'defaultValue' => null,
            'size' => 255,
            'comment' => '',
        ])
        ->addColumn('created_at', 'datetime', [
            'nullable' => true,
            'defaultValue' => null,
            'comment' => '',
        ])
        ->addColumn('updated_at', 'datetime', [
            'nullable' => true,
            'defaultValue' => null,
            'comment' => '',
        ])
        ->addColumn('deleted_at', 'datetime', [
            'nullable' => true,
            'defaultValue' => null,
            'comment' => '',
        ])
        ->addIndex(['email'], ['unique' => true, 'name' => 'users_email_unique'])
        ->create();
    }

    public function down(): void
    {
        $this->table('users')->drop();
    }
}

