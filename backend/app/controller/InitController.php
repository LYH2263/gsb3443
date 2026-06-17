<?php

namespace app\controller;

use app\model\User;
use think\facade\Log;
use think\Request;

class InitController
{
    public function init(Request $request)
    {
        $this->initAdminPassword();
        $this->initBrowseHistoryTable();
        return json_success([], '初始化完成');
    }

    public function initBrowseHistoryTable()
    {
        try {
            \think\facade\Db::query("SELECT 1 FROM `browse_history` LIMIT 1");
        } catch (\Exception $e) {
            $sql = "CREATE TABLE IF NOT EXISTS `browse_history` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
                `album_id` INT UNSIGNED NOT NULL COMMENT '画册ID',
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                KEY `idx_user` (`user_id`),
                KEY `idx_user_album` (`user_id`, `album_id`),
                KEY `idx_user_created` (`user_id`, `created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户浏览记录表'";
            \think\facade\Db::execute($sql);
            Log::info('初始化 browse_history 表成功');
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
