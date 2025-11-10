## ReactPHP-X Project Skeleton

A Laravel-like project structure built on ReactPHP-X, featuring routing, logging, and async database access. Uses Laravel's container and supports controller@method routes.

### Features
- **Routes**: Controller@method via `reactphp-x/route`.
- **Config**: `config/*.php` with `config()` helper.
- **Env**: `.env` via `vlucas/phpdotenv`.
- **Logging**: Channel-based config, single filesystem adapter.
- **Database**: Async MySQL using Cycle via `reactphp-x/cycle-database`.

### Requirements
- PHP >= 8.1

### Quick Start
```bash
composer install
cp .env.example .env
composer start
# visit http://127.0.0.1:8080/api/hello
```

### Commands
```bash
# Simple hello command
composer hello

# Database operations example (SELECT, INSERT, UPDATE, DELETE, UPSERT)
# Before running, import the table structure:
# mysql -u root -p your_database < app/Commands/database-example.sql
composer db-example
```

### Directory Structure
```
app/
  Commands/
  Http/Controllers/
bootstrap/
  app.php
  helpers.php
config/
  app.php
  logging.php
  database.php
public/
  index.php
routes/
  api.php
storage/
  logs/
vendor/
```

### Configuration
- `config/app.php`: app name, env, debug, timezone, listen address
- `config/logging.php`: channels and defaults
- `config/database.php`: async MySQL connection and pool

Helpers:
```php
// Config helper
config('app.listen');

// Database helpers
db()->query('SELECT * FROM users')->fetchAll();
table('users')->where('id', 1)->fetchAll();
table('users')->insert()->values($data)->run();
table('users')->update($data, ['id' => 1])->run();
table('users')->upsert()->conflicts(['email'])->values($data)->updates($fields)->run();
```

### Routing
Defined in `routes/api.php` using controller@method syntax.
```php
// routes/api.php
$route->group('/api', function (\ReactphpX\Route\Route $route) {
    $route->get('/hello', \App\Http\Controllers\HelloController::class . '@index');
});
```

Example controller:
```php
namespace App\Http\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use React\Http\Message\Response;

class HelloController
{
    public function index(Request $request)
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode(['message' => 'Hello, API!']));
    }
}
```

### HTTP Entry
`public/index.php` wires the router and reads listen address from `config('app.listen')`.

### Filesystem & Logging
- Filesystem adapter is a container singleton `fs`.
- Logging configuration is loaded from `config/logging.php` and uses that adapter across channels.

### Environment
Copy `.env.example` to `.env` and adjust:
```
APP_NAME, APP_ENV, APP_DEBUG, APP_TIMEZONE, LOG_LEVEL, X_LISTEN
DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD, DB_CHARSET
DB_POOL_MIN, DB_POOL_MAX, DB_POOL_QUEUE, DB_POOL_TIMEOUT
```

### Reference
- Routing package: [reactphp-x/route](https://github.com/reactphp-x/route)

### License
MIT


