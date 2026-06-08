<?php

declare(strict_types=1);

use App\Models\User;
use App\Repositories\UserPersistRepository;

test('save persists user', function (): void {
    $repository = repo(User::class);

    $user = new User();
    $user->name = 'Alice';
    $user->email = 'alice@example.com';
    $user->status = 1;

    $repository->save($user);

    expect($user->id)->toBeGreaterThan(0);

    $found = $repository->findByPK($user->id);
    expect($found)->toBeInstanceOf(User::class)
        ->and($found->name)->toBe('Alice')
        ->and($found->email)->toBe('alice@example.com');
});

test('withActive filters inactive users', function (): void {
    $repository = repo(User::class);

    $active = new User();
    $active->name = 'Active';
    $active->email = 'active@example.com';
    $active->status = 1;
    $repository->save($active);

    $inactive = new User();
    $inactive->name = 'Inactive';
    $inactive->email = 'inactive@example.com';
    $inactive->status = 0;
    $repository->save($inactive);

    $activeUsers = $repository->withActive()->findAll([]);

    expect($activeUsers)->toHaveCount(1)
        ->and($activeUsers[0]->name)->toBe('Active');
});

test('not deleted scope excludes soft deleted users', function (): void {
    $repository = repo(User::class);

    $user = new User();
    $user->name = 'Deleted';
    $user->email = 'deleted@example.com';
    $user->status = 1;
    $user->deletedAt = new DateTimeImmutable('now');
    $repository->save($user);

    expect($repository->findByPK($user->id))->toBeNull();
});
