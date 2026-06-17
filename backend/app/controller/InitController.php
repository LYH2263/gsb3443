<?php

namespace app\controller;

use app\model\User;
use think\facade\Db;
use think\facade\Log;
use think\Request;

class InitController
{
    public function init(Request $request)
    {
        $this->ensureTables();
        $this->initAdminPassword();
        return json_success([], '初始化完成');
    }

    /**
     * 幂等地补齐增量数据表。
     * init.sql 仅在数据库首次初始化时执行，对已存在的数据卷不生效，
     * 因此这里在每次初始化时确保新增表存在，避免既有环境缺表导致 500。
     */
    public function ensureTables()
    {
        try {
            Db::execute("CREATE TABLE IF NOT EXISTS `browse_histories` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
                `album_id` INT UNSIGNED NOT NULL COMMENT '画册ID',
                `browse_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '浏览时间',
                KEY `idx_user` (`user_id`),
                KEY `idx_album` (`album_id`),
                UNIQUE KEY `uk_user_album` (`user_id`, `album_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='浏览历史表'");
        } catch (\Exception $e) {
            Log::error('ensureTables failed: ' . $e->getMessage());
        }
    }

    public function initAdminPassword()
    {
        $accounts = [
            ['username' => 'admin', 'password' => '123456'],
            ['username' => 'testuser', 'password' => '123456'],
            ['username' => 'vipuser', 'password' => '123456'],
        ];

        foreach ($accounts as $account) {
            $user = User::where('username', $account['username'])->find();
            if ($user) {
                $rawPassword = $user->getData('password');
                if (str_contains($rawPassword, 'placeholder') || !password_verify($account['password'], $rawPassword)) {
                    $user->password = $account['password'];
                    $user->save();
                    Log::info("初始化用户密码: {$account['username']}");
                }
            }
        }
    }

    public function health()
    {
        try {
            \think\facade\Db::query("SELECT 1");
            return json_success([
                'status'    => 'ok',
                'timestamp' => date('Y-m-d H:i:s'),
                'database'  => 'connected',
            ]);
        } catch (\Exception $e) {
            Log::error("Health check failed: " . $e->getMessage());
            return json_error('数据库连接异常', 500);
        }
    }
}
