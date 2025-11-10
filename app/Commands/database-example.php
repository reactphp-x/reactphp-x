<?php

/**
 * 数据库操作示例
 * 
 * 使用前请先导入表结构：
 * mysql -u root -p your_database < app/Commands/database-example.sql
 * 
 * 或者在MySQL中执行：
 * source /path/to/app/Commands/database-example.sql
 */

require __DIR__ . '/../../vendor/autoload.php';

use ReactphpX\Log\Log;
use function React\Async\async;
use function React\Async\await;

$container = require __DIR__ . '/../../bootstrap/app.php';

Log::info('数据库操作示例开始 at ' . date('Y-m-d H:i:s'));
echo "=== 数据库操作示例 ===\n\n";

// 示例 1: 查询数据
async(function () {
    try {
        echo "1. 查询示例 (SELECT)\n";
        echo "------------------------\n";
        
        // 简单查询
        $users = table('users')
            ->select()
            ->limit(10)
            ->fetchAll();
        
        echo "查询到 " . count($users) . " 条记录\n";
        if (!empty($users)) {
            $firstUser = is_array($users[0]) ? (object) $users[0] : $users[0];
            echo "第一条记录: " . json_encode($firstUser, JSON_UNESCAPED_UNICODE) . "\n";
        }
        echo "\n";

        // 条件查询
        echo "2. 条件查询 (WHERE)\n";
        echo "------------------------\n";
        
        $user = table('users')
            ->select()
            ->where('id', 1)
            ->fetchAll();
        
        if (!empty($user)) {
            echo "找到 ID=1 的用户: " . json_encode($user[0], JSON_UNESCAPED_UNICODE) . "\n";
        } else {
            echo "未找到 ID=1 的用户\n";
        }
        echo "\n";

        // 复杂条件查询
        echo "3. 复杂条件查询 (多个WHERE)\n";
        echo "------------------------\n";
        
        $activeUsers = table('users')
            ->select()
            ->where([
                'status' => 1,
                'is_active' => true
            ])
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->fetchAll();
        
        echo "查询到 " . count($activeUsers) . " 个活跃用户\n\n";

        // 聚合查询
        echo "4. 聚合查询 (MAX/MIN/COUNT)\n";
        echo "------------------------\n";
        
        $maxId = table('users')->select()->max('id');
        echo "用户表最大 ID: " . $maxId . "\n";
        
        $minId = table('users')->select()->min('id');
        echo "用户表最小 ID: " . $minId . "\n\n";

        // 插入数据
        echo "5. 插入数据 (INSERT)\n";
        echo "------------------------\n";
        
        $insertData = [
            'name' => '测试用户_' . date('His'),
            'email' => 'test_' . time() . '@example.com',
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        
        table('users')->insert()
            ->values($insertData)
            ->run();
        
        echo "成功插入新用户: " . $insertData['name'] . "\n\n";

        // 批量插入
        echo "6. 批量插入 (BATCH INSERT)\n";
        echo "------------------------\n";
        
        $batchData = [];
        for ($i = 1; $i <= 3; $i++) {
            $batchData[] = [
                'name' => "批量用户_{$i}_" . date('His'),
                'email' => "batch_{$i}_" . time() . "@example.com",
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        }
        
        table('users')->insert()
            ->columns(array_keys($batchData[0]))
            ->values($batchData)
            ->run();
        
        echo "成功批量插入 " . count($batchData) . " 个用户\n\n";

        // 更新数据
        echo "7. 更新数据 (UPDATE)\n";
        echo "------------------------\n";
        
        table('users')->update(
            [
                'status' => 2,
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'email' => $insertData['email'],
            ]
        )->run();
        
        echo "成功更新用户状态\n\n";

        // Upsert 操作 (插入或更新)
        echo "8. Upsert 操作 (INSERT ON DUPLICATE KEY UPDATE)\n";
        echo "------------------------\n";
        
        $upsertData = [
            [
                'username' => 'user001',
                'name' => 'User One',
                'email' => 'user001@example.com',
                'status' => 1,
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'username' => 'user002',
                'name' => 'User Two',
                'email' => 'user002@example.com',
                'status' => 1,
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];
        
        table('users')->upsert()
            ->conflicts(['username'])  // 冲突字段
            ->columns(array_keys($upsertData[0]))
            ->values($upsertData)
            ->updates(['name', 'email', 'status', 'updated_at'])  // 冲突时更新的字段
            ->run();
        
        echo "成功执行 Upsert 操作\n\n";

        // 原生 SQL 执行
        echo "9. 原生 SQL 执行\n";
        echo "------------------------\n";
        
        $sql = "SELECT COUNT(*) as total FROM users WHERE status = 1";
        $result = db()->query($sql)->fetchAll();
        
        if (!empty($result)) {
            $count = is_array($result[0]) ? $result[0]['total'] : $result[0]->total;
            echo "活跃用户总数: " . $count . "\n";
        }
        echo "\n";

        // 删除数据
        echo "10. 删除数据 (DELETE)\n";
        echo "------------------------\n";
        
        table('users')->delete()
            ->where('email', 'LIKE', 'test_%@example.com')
            ->run();
        
        echo "成功删除测试用户\n\n";

        Log::info('数据库操作示例结束 at ' . date('Y-m-d H:i:s'));
        echo "=== 示例完成 ===\n";
        
    } catch (\Exception $e) {
        Log::channel('error')->error('数据库操作示例出错: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);
        echo "❌ 错误: " . $e->getMessage() . "\n";
    }
})();

