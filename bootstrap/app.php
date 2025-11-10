<?php

use Dotenv\Dotenv;
use DI\ContainerBuilder;
use ReactphpX\Log\Log;
use Cycle\Database\Config as Config;
use ReactphpX\CycleDatabase\AsyncDatabaseManager;
use ReactphpX\CycleDatabase\AsyncMySQLDriverConfig;
use ReactphpX\CycleDatabase\AsyncTcpConnectionConfig;
use Cycle\Database\LoggerFactoryInterface;
use Cycle\Database\Driver\DriverInterface;

$basePath = realpath(__DIR__ . '/..');

require_once $basePath . '/vendor/autoload.php';

// Load environment
$dotenv = Dotenv::createImmutable($basePath);
$dotenv->safeLoad();

date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Shanghai'));

// IoC container
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions([
    'fs' => function () {
        return React\Filesystem\Factory::createRpc('127.0.0.1:8080', true);
    },
    'db' => function () {
        $db = config('database');
        $mysql = $db['connections']['mysql'];
        return new AsyncDatabaseManager(new Config\DatabaseConfig([
            'default' => $db['default'],
            'databases' => $db['databases'],
            'connections' => [
                'mysql' => new AsyncMySQLDriverConfig(
                    connection: new AsyncTcpConnectionConfig(
                        database: $mysql['database'],
                        host: $mysql['host'],
                        port: (int) $mysql['port'],
                        charset: $mysql['charset'],
                        user: $mysql['user'],
                        password: $mysql['password']
                    ),
                    options: array_merge($mysql['pool'], [
                        'logInterpolatedQueries' => true,
                    ])
                ),
            ],
        ]), new class implements LoggerFactoryInterface {
            public function getLogger(?DriverInterface $driver = null): Psr\Log\LoggerInterface
            {
                return Log::channel('sql');
            }
        });
    },
]);
$container = $containerBuilder->build();

// Store container instance globally for helpers
$GLOBALS['__container'] = $container;

// Logging (configured via config/logging.php)
$logging = config('logging');
if ($logging) {
    Log::configure($logging);
}

return $container;


