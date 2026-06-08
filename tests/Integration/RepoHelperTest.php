<?php

declare(strict_types=1);

use App\Models\User;
use App\Repositories\UserPersistRepository;

test('repo returns user repository', function (): void {
    $repository = repo(User::class);

    expect($repository)->toBeInstanceOf(UserPersistRepository::class);
});

test('repo accepts custom orm instance', function (): void {
    $orm = orm();
    $repository = repo(User::class, $orm);

    expect($repository)->toBeInstanceOf(UserPersistRepository::class);
});
