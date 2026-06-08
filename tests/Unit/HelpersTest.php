<?php

declare(strict_types=1);

use Tests\Support\ConfigCache;
use Tests\Support\ShutsDownReactLoop;

test('base path returns project root', function (): void {
    expect(base_path())->toBe(realpath(__DIR__ . '/../..'))
        ->and(base_path('config'))->toBe(realpath(__DIR__ . '/../..') . '/config');
});

test('config files contain expected values', function (): void {
    $app = require config_path('app.php');
    $database = require config_path('database.php');

    expect($app['name'])->toBe('ReactPHPX')
        ->and($database['default'])->toBe('default');
});

test('config returns default for missing keys', function (): void {
    ConfigCache::reset();
    unset($GLOBALS['__container']);

    $_ENV['DB_DRIVER'] = 'sqlite';
    $_ENV['DB_DATABASE'] = ':memory:';
    $_ENV['DB_SQLITE_IDLE'] = '-1';
    $_ENV['APP_DEBUG'] = 'true';
    $_SERVER['DB_DRIVER'] = 'sqlite';
    $_SERVER['DB_DATABASE'] = ':memory:';
    $_SERVER['DB_SQLITE_IDLE'] = '-1';
    $_SERVER['APP_DEBUG'] = 'true';

    require base_path('bootstrap/app.php');

    expect(config('database.missing.key'))->toBeNull()
        ->and(config('database.missing.key', 'fallback'))->toBe('fallback');

    ShutsDownReactLoop::closeDatabase();
    ConfigCache::reset();
});

test('env reads from environment', function (): void {
    $_ENV['TEST_HELPER_KEY'] = 'value';
    $_SERVER['TEST_HELPER_KEY'] = 'value';

    expect(env('TEST_HELPER_KEY'))->toBe('value')
        ->and(env('TEST_HELPER_MISSING', 'default'))->toBe('default');

    unset($_ENV['TEST_HELPER_KEY'], $_SERVER['TEST_HELPER_KEY']);
});

test('getMemoryUsage returns expected keys', function (): void {
    $usage = getMemoryUsage();

    expect($usage)->toHaveKeys([
        'date',
        'current_usage_mb',
        'current_usage_real_mb',
        'peak_usage_mb',
        'peak_usage_real_mb',
    ])->and($usage['current_usage_mb'])->toBeGreaterThan(0);
});

test('app throws when container is not initialized', function (): void {
    unset($GLOBALS['__container']);

    app();
})->throws(RuntimeException::class, 'Container not initialized');
