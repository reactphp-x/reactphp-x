## ReactPHP-X Project Skeleton

A Laravel-like project structure built on ReactPHP-X, featuring routing, logging, and async database access. Uses Laravel's container and supports controller@method routes.

### Features
- **Routes**: Controller@method via `reactphp-x/route`.
- **Config**: `config/*.php` with `config()` helper.
- **Env**: `.env` via `vlucas/phpdotenv`.
- **Logging**: Channel-based config, single filesystem adapter.
- **Database**: Async MySQL using Cycle via `reactphp-x/cycle-database`.
- **ORM**: Cycle ORM with annotated entities and migrations support.

### Requirements
- PHP >= 8.1

### Quick Start
```bash
composer install
cp .env.example .env
composer start
# visit http://127.0.0.1:8080/api/hello
```

### Development (Hot Reload)

ReactPHP runs as a long-lived process; code changes do not apply until the server restarts. Use [nodemon](https://github.com/remy/nodemon) via `npx` to watch source files and restart automatically (requires Node.js/npm):

```bash
npx nodemon \
  -e php,env \
  --watch app \
  --watch routes \
  --watch config \
  --watch bootstrap \
  --watch public/index.php \
  --watch .env \
  --exec "php -d variables_order=EGPCS public/index.php"
```

- Type `rs` and press Enter to restart manually.
- Press Ctrl+C to stop.

### Commands
```bash
# Simple hello command
composer hello

# Database operations example (SELECT, INSERT, UPDATE, DELETE, UPSERT)
# Before running, import the table structure:
# mysql -u root -p your_database < app/Commands/database-example.sql
composer db-example

# ORM example
php app/Commands/orm-example.php

# Database migrations
php app/Commands/migrations.php [action]
# Actions: list, status, run, rollback
```

### Directory Structure
```
app/
  Commands/
  Http/Controllers/
  Models/          # ORM entities
  Repositories/    # Custom repositories
bootstrap/
  app.php
  helpers.php
config/
  app.php
  logging.php
  database.php
migrations/        # Database migrations
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

// ORM helper
orm()->getRepository(User::class)->findByPK(1);
orm()->getRepository(User::class)->findAll([]);
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

### ORM (Cycle ORM)

This project uses Cycle ORM with PHP 8 attributes for entity definitions.

#### Entity Definition

Define entities in `app/Models/` using PHP 8 attributes:

```php
namespace App\Models;

use Cycle\Annotated\Annotation as Cycle;

#[Cycle\Entity(table: 'users', repository: \App\Repositories\UserRepository::class)]
class User
{
    #[Cycle\Column(type: 'primary')]
    public int $id;

    #[Cycle\Column(type: 'string')]
    public string $name;

    #[Cycle\Column(type: 'smallInteger')]
    public int $status = 0;

    #[Cycle\Column(type: 'string', nullable: true)]
    public ?string $avatar = null;
    
    #[Cycle\Column(type: 'datetime', nullable: true)]
    public ?\DateTimeInterface $createdAt = null;

    #[Cycle\Column(type: 'datetime', nullable: true)]
    public ?\DateTimeInterface $updatedAt = null;
}
```

#### Custom Repository

Create custom repositories in `app/Repositories/`:

```php
namespace App\Repositories;

class UserRepository extends \Cycle\ORM\Select\Repository
{
    public function withActive(): self
    {
        $repository = clone $this;
        $repository->select->where('status', 1);
        return $repository;
    }
}
```

#### Usage Examples

```php
use App\Models\User;

// Get repository
$repository = orm()->getRepository(User::class);

// Find by primary key
$user = $repository->findByPK(1);

// Find all with custom scope
$activeUsers = $repository->withActive()->findAll([]);

// Find all
$users = $repository->findAll([]);
```

#### Updating Relation Fields

When a column is also used as the `innerKey` of a `BelongsTo` relation, assigning the foreign key directly (e.g. `$user->managerId = 2`) often **does not persist**, because Cycle ORM keeps a `Reference` snapshot for the relation and may overwrite the FK on save.

**Always use the same ORM instance** for load and save within one update flow.

The examples below use a self-referential `User` → `manager` relation (`manager_id` column):

```php
// Entity excerpt (app/Models/User.php)
#[Cycle\Column(type: 'integer', nullable: true)]
public ?int $managerId = null;

#[Cycle\Column(type: 'integer', nullable: true, name: 'manager_id')]
public ?int $aliasManagerId = null;

#[Cycle\Relation\BelongsTo(target: User::class, innerKey: 'managerId', nullable: true)]
private ?User $manager = null;

public function assignManager(User $manager): void
{
    $this->manager = $manager;
}
```

**Method 1 — Alias column (update FK only, no relation load)**

Map a second property to the same DB column that is **not** bound to the relation:

```php
$orm = orm();
$userRepository = $orm->getRepository(User::class);
$user = $userRepository->select()->wherePK(1)->fetchOne();

$user->aliasManagerId = 2;  // persists
// $user->managerId = 2;   // does NOT persist (relation FK)

$userRepository->save($user);
```

**Method 2 — Preload relation, then update FK**

Eager-load the relation first; after that, updating the FK property works:

```php
$orm = orm();
$userRepository = $orm->getRepository(User::class);
$user = $userRepository->select()->load('manager')->wherePK(1)->fetchOne();

$user->managerId = 2;  // persists after relation is loaded

$userRepository->save($user);
```

**Method 3 — Set related entity (recommended)**

Assign the related model (or a dedicated helper) so ORM syncs the FK:

```php
$orm = orm();
$userRepository = $orm->getRepository(User::class);
$user = $userRepository->findByPK(1);
$manager = $orm->getRepository(User::class)->findByPK(2);

$user->manager = $manager;
// or: $user->assignManager($manager);

$userRepository->save($user);
```

| Method | When to use |
|--------|-------------|
| Alias column | Only need to change FK; avoid loading related entity |
| Preload + FK | Relation data already needed; update FK in same query |
| Set entity / `assign*` | Semantically correct; keeps relation and FK in sync |

Note: When updating, you must use the same orm instance, i.e., orm(). For each request, you should not attach or reuse the same orm() instance globally. For example, if a service depends on UserPersistRepository, each time the service is used, a new instance of UserPersistRepository must be created. 

#### Migrations

Manage database migrations:

```bash
# List migrations
php app/Commands/migrations.php list

# Run pending migrations
php app/Commands/migrations.php run

# Rollback last migration
php app/Commands/migrations.php rollback
```

Migration files are stored in `migrations/` directory with format:
`YYYYMMDD.HHMMSS_0_0_default_description.php`

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


