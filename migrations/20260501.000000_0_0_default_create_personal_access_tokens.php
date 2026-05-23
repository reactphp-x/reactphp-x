<?php

declare(strict_types=1);

namespace Migration;

use Cycle\Migrations\Migration;

class CreatePersonalAccessTokens extends Migration
{
    protected const DATABASE = 'default';

    public function up(): void
    {

        $this->table('personal_access_tokens')
            ->addColumn('id', 'bigPrimary', [
                'autoIncrement' => true,
                'nullable' => false,
                'unsigned' => true,
            ])
            ->addColumn('tokenable_type', 'string', [
                'nullable' => false,
                'size' => 255,
            ])
            ->addColumn('tokenable_id', 'bigInteger', [
                'nullable' => false,
                'unsigned' => true,
            ])
            ->addColumn('name', 'text', [
                'nullable' => false,
            ])
            ->addColumn('token', 'string', [
                'nullable' => false,
                'size' => 64,
            ])
            ->addColumn('abilities', 'text', [
                'nullable' => true,
            ])
            ->addColumn('last_used_at', 'timestamp', [
                'nullable' => true,
            ])
            ->addColumn('expires_at', 'timestamp', [
                'nullable' => true,
            ])
            ->addColumn('created_at', 'timestamp', [
                'nullable' => true,
            ])
            ->addColumn('updated_at', 'timestamp', [
                'nullable' => true,
            ])
            ->addIndex(['token'], ['unique' => true, 'name' => 'xcx_personal_access_tokens_token_unique'])
            ->addIndex(['tokenable_type', 'tokenable_id'], ['name' => 'xcx_personal_access_tokens_tokenable_type_tokenable_id_index'])
            ->addIndex(['expires_at'], ['name' => 'xcx_personal_access_tokens_expires_at_index'])
            ->create();
    }

    public function down(): void
    {
        $this->table('personal_access_tokens')->drop();
    }
}
