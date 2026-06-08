<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\User;

final class UserFactory
{
    public static function create(
        string $name = 'Demo',
        string $email = 'demo@example.com',
        int $status = 1,
    ): User {
        $user = new User();
        $user->name = $name;
        $user->email = $email;
        $user->status = $status;

        repo(User::class)->save($user);

        return $user;
    }
}
