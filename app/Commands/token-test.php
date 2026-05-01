<?php

declare(strict_types=1);

/**
 * Issue a personal access token for a user and verify it via PersonalAccessToken::findToken().
 *
 * Usage: php app/Commands/token-test.php [user_id] [token_name]
 * Example: php app/Commands/token-test.php 1 my-cli
 */

require __DIR__ . '/../../vendor/autoload.php';

use App\Models\PersonalAccessToken;
use App\Models\User;

$container = require __DIR__ . '/../../bootstrap/app.php';

$userId = (int) ($argv[1] ?? 1);
$name = (string) ($argv[2] ?? 'cli-test');

$user = app('orm')->getRepository(User::class)->findByPK($userId);
if (! $user instanceof User) {
    fwrite(STDERR, "User id={$userId} not found.\n");
    exit(1);
}

$issued = $user->createToken($name, ['*'], null);

echo "Issued token (send as Authorization: Bearer … or ?token=…):\n";
echo $issued->plainTextToken . "\n\n";

$resolved = PersonalAccessToken::findToken($issued->plainTextToken);
if ($resolved === null) {
    fwrite(STDERR, "findToken() could not resolve the token just created.\n");
    exit(2);
}

$owner = $resolved->tokenable;
$ownerId = $owner instanceof User ? (string) $owner->id : 'unknown';

echo "ORM verify OK: personal_access_tokens.id={$resolved->id}, tokenable user id={$ownerId}\n";
