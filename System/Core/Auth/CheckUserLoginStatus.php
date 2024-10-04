<?php

namespace ChatRoom\Core\Auth;

use ChatRoom\Core\Auth\TokenManager;

class CheckUserLoginStatus
{
    protected $tokenManager;

    // 通过构造函数进行依赖注入，便于测试
    public function __construct()
    {
        $this->tokenManager = new TokenManager;;
    }

    public function check()
    {
        // 检查会话信息是否存在
        if (!isset($_SESSION['user_login_info']['user_login_token']) || !isset($_SESSION['user_login_info']['user_id'])) {
            return false; // 如果会话信息不完整，返回false
        }
        try {
            // 使用TokenManager验证令牌
            return $this->tokenManager->validateToken($_SESSION['user_login_info']['user_login_token'], $_SESSION['user_login_info']['user_id']);
        } catch (\Exception $e) {
            // 捕获异常并记录错误日志（可以使用日志系统记录异常）
            error_log($e->getMessage());
            return false; // 发生错误时返回false
        }
    }
}
