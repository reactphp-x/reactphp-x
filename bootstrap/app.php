<?php

use Dotenv\Dotenv;
use DI\ContainerBuilder;
use ReactphpX\Log\Log;
use Cycle\Database\Config as Config;
use Cycle\Database\Config\SQLite\FileConnectionConfig;
use Cycle\Database\Config\SQLite\MemoryConnectionConfig;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\LoggerFactoryInterface;
use ReactphpX\CycleDatabase\AsyncDatabaseManager;
use ReactphpX\CycleDatabase\AsyncMySQLDriverConfig;
use ReactphpX\CycleDatabase\AsyncSQLiteDriverConfig;
use ReactphpX\CycleDatabase\AsyncTcpConnectionConfig;

use Cycle\Schema;
use Cycle\Annotated;
use Cycle\Annotated\Locator\TokenizerEmbeddingLocator;
use Cycle\Annotated\Locator\TokenizerEntityLocator;
use Doctrine\Common\Annotations\AnnotationReader;
use Cycle\ORM;


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
        if (env('APP_DEBUG') === 'true') {
            return new React\Filesystem\Fallback\Adapter;
        }
        return React\Filesystem\Factory::createRpc('127.0.0.1:8080', false);
    },
    'db' => function () {
        $config = config('database');

        $driverFactories = [
            'mysql' => static function (array $connection): AsyncMySQLDriverConfig {
                return new AsyncMySQLDriverConfig(
                    connection: new AsyncTcpConnectionConfig(
                        database: $connection['database'],
                        host: $connection['host'],
                        port: (int) $connection['port'],
                        charset: $connection['charset'],
                        user: $connection['user'],
                        password: $connection['password'],
                    ),
                    timezone: $connection['timezone'] ?? 'Asia/Shanghai',
                    options: array_merge($connection['pool'] ?? [], $connection['options'] ?? []),
                );
            },
            'sqlite' => static function (array $connection): AsyncSQLiteDriverConfig {
                $database = $connection['database'];
                $connectionConfig = $database === ':memory:'
                    ? new MemoryConnectionConfig()
                    : new FileConnectionConfig($database);

                if ($database !== ':memory:') {
                    $directory = dirname($database);
                    if ($directory !== '' && ! is_dir($directory)) {
                        @mkdir($directory, 0777, true);
                    }
                }

                return new AsyncSQLiteDriverConfig(
                    connection: $connectionConfig,
                    timezone: $connection['timezone'] ?? 'Asia/Shanghai',
                    options: $connection['options'] ?? [],
                );
            },
        ];

        $connections = [];
        foreach ($config['connections'] ?? [] as $name => $connection) {
            $driver = $connection['driver'] ?? $name;

            if (! isset($driverFactories[$driver])) {
                throw new InvalidArgumentException(
                    "Unsupported database driver [{$driver}] for connection [{$name}]."
                );
            }

            $connections[$name] = $driverFactories[$driver]($connection);
        }

        return new AsyncDatabaseManager(
            new Config\DatabaseConfig([
                'default' => $config['default'],
                'databases' => $config['databases'],
                'connections' => $connections,
            ]),
            new class implements LoggerFactoryInterface {
                public function getLogger(?DriverInterface $driver = null): Psr\Log\LoggerInterface
                {
                    return Log::channel('sql');
                }
            }
        );
    },
    'orm' => function () {
        $finder = (new \Symfony\Component\Finder\Finder())->files()->in([base_path('app/Models')]);
        $classLocator = new \Spiral\Tokenizer\ClassLocator($finder);
        $embeddingLocator = new TokenizerEmbeddingLocator($classLocator);
        $entityLocator = new TokenizerEntityLocator($classLocator);
        $schema = (new Schema\Compiler())->compile(new Schema\Registry(app('db')), [
            new Schema\Generator\ResetTables(),             // Reconfigure table schemas (deletes columns if necessary)
            new Annotated\Embeddings($embeddingLocator),    // Recognize embeddable entities
            new Annotated\Entities($entityLocator),         // Identify attributed entities
            new Annotated\TableInheritance(),               // Setup Single Table or Joined Table Inheritance
            new Annotated\MergeColumns(),                   // Integrate table #[Column] attributes
            new Schema\Generator\GenerateRelations(),       // Define entity relationships
            new Schema\Generator\GenerateModifiers(),       // Apply schema modifications
            new Schema\Generator\ValidateEntities(),        // Ensure entity schemas adhere to conventions
            new Schema\Generator\RenderTables(),            // Create table schemas
            new Schema\Generator\RenderRelations(),         // Establish keys and indexes for relationships
            new Schema\Generator\RenderModifiers(),         // Implement schema modifications
            new Schema\Generator\ForeignKeys(),             // Define foreign key constraints
            new Annotated\MergeIndexes(),                // Merge table index attributes             // Align table changes with the database
            new Schema\Generator\GenerateTypecast(),        // Typecast non-string columns
        ]);
        return new ORM\ORM(new ORM\Factory(app('db')), new ORM\Schema($schema));
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

// Build ORM once before the React loop handles concurrent requests. PHP-DI tracks
// in-flight resolutions on the container (not per-fiber); a slow `orm` factory that
// suspends on async I/O can overlap with another request's `get('orm')` and false
// "Circular dependency: orm -> orm".
$container->get('orm');

return $container;
