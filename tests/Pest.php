<?php

declare(strict_types=1);

use React\EventLoop\Loop;
use Tests\Support\ShutsDownReactLoop;
use Tests\Support\SqliteDatabase;

uses()
    ->beforeEach(function (): void {
        SqliteDatabase::bootstrap();
        SqliteDatabase::migrate();
    })
    ->afterEach(function (): void {
        ShutsDownReactLoop::closeDatabase();
    })
    ->in('Integration');

// register_shutdown_function(static function (): void {
//     Loop::addTimer(10.0, static function (): void {
//         Loop::stop();
//     });

//     ShutsDownReactLoop::shutdown();
//     Loop::run();
// });
