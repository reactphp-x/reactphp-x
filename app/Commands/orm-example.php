<?php

require __DIR__ . '/../../vendor/autoload.php';

use ReactphpX\Log\Log;
use App\Models\User;

$container = require __DIR__ . '/../../bootstrap/app.php'; 

$userRepository = app('orm')->getRepository(User::class);
$users = $userRepository->withActive()->findAll([]);
// $users = app('orm')->getRepository(User::class)->findByPK(1);

foreach ($users as $user) {
    echo $user->name . "\n";
}

$user = new User();
$user->name = 'test';
$user->status = 1;
$userRepository->save($user);

$users = $userRepository->withActive()->findAll([]);
foreach ($users as $user) {
    echo $user->name . "\n";
}


