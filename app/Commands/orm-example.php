<?php

require __DIR__ . '/../../vendor/autoload.php';

use ReactphpX\Log\Log;
use App\Models\User;

$container = require __DIR__ . '/../../bootstrap/app.php'; 

$users = app('orm')->getRepository(User::class)->withActive()->findAll([]);
// $users = app('orm')->getRepository(User::class)->findByPK(1);

foreach ($users as $user) {
    echo $user->name . "\n";
}


