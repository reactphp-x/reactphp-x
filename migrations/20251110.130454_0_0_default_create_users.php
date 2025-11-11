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
        ->addColumn('id', 'primary', [
            'nullable' => false,
            'defaultValue' => null,
            'size' => 11,
            'autoIncrement' => true,
            'unsigned' => false,
            'zerofill' => false,
            'comment' => '',
        ])
        ->addColumn('name', 'string', [
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
        ->setPrimaryKeys(['id'])
        ->create();
    }

    public function down(): void
    {
        $this->table('users')->drop();
    }
}

