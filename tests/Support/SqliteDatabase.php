<?php

declare(strict_types=1);

namespace Tests\Support;

use Cycle\Migrations\Config\MigrationConfig;
use Cycle\Migrations\FileRepository;
use Cycle\Migrations\Migrator;

final class SqliteDatabase
{
    public static function bootstrap(): void
    {
        unset($GLOBALS['__container']);
        ConfigCache::reset();

        $_ENV['DB_DRIVER'] = 'sqlite';
        $_ENV['DB_DATABASE'] = ':memory:';
        $_ENV['DB_SQLITE_IDLE'] = '-1';
        $_ENV['APP_DEBUG'] = 'true';
        $_SERVER['DB_DRIVER'] = 'sqlite';
        $_SERVER['DB_DATABASE'] = ':memory:';
        $_SERVER['DB_SQLITE_IDLE'] = '-1';
        $_SERVER['APP_DEBUG'] = 'true';

        require base_path('bootstrap/app.php');
    }

    public static function migrate(): void
    {
        $config = new MigrationConfig([
            'directory' => base_path('migrations'),
            'table' => 'migrations',
            'namespace' => 'Migration',
        ]);

        $migrator = new Migrator($config, app('db'), new FileRepository($config));
        $migrator->configure();

        while ($migrator->run() !== null) {
        }
    }
}
