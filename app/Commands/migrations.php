<?php

/**
 * 数据库迁移命令
 * 
 * 基于 Cycle ORM 的迁移功能，用于管理数据库迁移
 * 
 * 使用方法：
 * php app/Commands/migrations.php [action]
 * 
 * action 可选值：
 *   - list: 列出所有迁移（默认）
 *   - run: 执行所有未执行的迁移
 *   - rollback: 回滚最近一次迁移
 *   - status: 显示迁移状态
 */

require __DIR__ . '/../../vendor/autoload.php';

use ReactphpX\Log\Log;
use Cycle\Migrations\Config\MigrationConfig;
use Cycle\Migrations\FileRepository;
use Cycle\Migrations\Migrator;
use function React\Async\async;
use Cycle\Migrations\State;

$container = require __DIR__ . '/../../bootstrap/app.php';

// 获取命令行参数
$action = $argv[1] ?? 'list';

Log::channel('sql')->info('Migration command started', ['action' => $action]);
echo "=== 数据库迁移管理 ===\n\n";

// 使用 bootstrap/app.php 中配置的数据库管理器
$dbal = app('db');

// 初始化日志
$logger = Log::channel('sql');
$dbConfig = config('database');
$mysql = $dbConfig['connections']['mysql'] ?? [];
Log::channel('sql')->info('Starting migrations', [
    'database' => $mysql['database'] ?? null,
    'action' => $action,
]);

// 迁移配置（迁移文件目录与迁移状态表）
$migrationsDir = base_path('migrations');
if (!is_dir($migrationsDir)) {
    @mkdir($migrationsDir, 0777, true);
    echo "创建迁移目录：{$migrationsDir}\n";
}

$migrationConfig = new MigrationConfig([
    'directory' => $migrationsDir,
    'table' => 'migrations',
    'namespace' => 'Migration',
]);
$repository = new FileRepository($migrationConfig);
$migrator = new Migrator($migrationConfig, $dbal, $repository);

async(function () use ($migrator, $action, $logger, $migrationsDir) {

    // 初始化迁移表
    $migrator->configure();

    echo "迁移目录：{$migrationsDir}\n";
    echo "迁移状态表：migrations\n\n";

    // 根据 action 执行相应操作
    switch ($action) {
        case 'list':
        case 'status':
            // 列出迁移
            $migrations = $migrator->getMigrations();
            echo "可用迁移数量：" . count($migrations) . "\n\n";

            if (empty($migrations)) {
                echo "没有找到迁移文件。\n";
                echo "提示：将迁移文件放在 {$migrationsDir} 目录下。\n";
            } else {
                echo "迁移列表：\n";
                echo str_repeat('-', 80) . "\n";
                printf("%-40s %-15s %s\n", "迁移名称", "状态", "创建时间");
                echo str_repeat('-', 80) . "\n";

                foreach ($migrations as $m) {
                    $state = $m->getState();
                    $status = $state->getStatus();
                    $statusText = match ($status) {
                        'pending' => '待执行',
                        'executed' => '已执行',
                        default => $status,
                    };
                    $timeCreated = $state->getTimeCreated()
                        ? $state->getTimeCreated()->format('Y-m-d H:i:s')
                        : 'N/A';

                    printf(
                        "%-40s %-15s %s\n",
                        $state->getName(),
                        $statusText,
                        $timeCreated
                    );
                }
                echo str_repeat('-', 80) . "\n";
            }
            break;

        case 'run':
            // 先列出迁移状态
            $migrations = $migrator->getMigrations();
            $pendingCount = 0;
            foreach ($migrations as $m) {
                echo "Migration: " . $m->getState()->getName() . " - Status: " . $m->getState()->getStatus() . "\n";
                if ($m->getState()->getStatus() === State::STATUS_PENDING) {
                    $pendingCount++;
                }
            }

            if ($pendingCount === 0) {
                echo "没有待执行的迁移。\n";
                return;
            }

            echo "待执行迁移数量：{$pendingCount}\n\n";

            // 执行所有未执行的迁移
            $executed = 0;
            while (true) {
                $migration = $migrator->run();
                if ($migration === null) {
                    break;
                }
                $executed++;
                $state = $migration->getState();
                echo "✓ 已执行迁移：{$state->getName()}\n";
                $logger->info('Migration executed', [
                    'name' => $state->getName(),
                ]);
            }

            if ($executed > 0) {
                echo "\n总共执行了 {$executed} 个迁移。\n";
            } else {
                echo "没有需要执行的迁移。\n";
            }
            break;

        case 'rollback':
            $rolledBack = $migrator->rollback();
            if ($rolledBack !== null) {
                $state = $rolledBack->getState();
                echo "✓ 已回滚迁移：{$state->getName()}\n";
                $logger->info('Migration rolled back', [
                    'name' => $state->getName(),
                ]);
            } else {
                echo "没有可回滚的迁移。\n";
            }
            break;

        default:
            echo "未知的操作：{$action}\n\n";
            echo "使用方法：\n";
            echo "  php app/Commands/migrations.php [action]\n\n";
            echo "可用的操作：\n";
            echo "  list      - 列出所有迁移（默认）\n";
            echo "  status    - 显示迁移状态（同 list）\n";
            echo "  run       - 执行所有未执行的迁移\n";
            echo "  rollback  - 回滚最近一次迁移\n";
            exit(1);
    }

    echo "\n=== 完成 ===\n";
    Log::channel('sql')->info('Migration command ended', ['action' => $action]);

})();


\React\EventLoop\Loop::addTimer(3, function () {
    \React\EventLoop\Loop::stop();
});