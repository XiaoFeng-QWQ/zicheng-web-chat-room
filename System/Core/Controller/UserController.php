<?php

namespace ChatRoom\Core\Controller;

use Exception;
use PDOException;
use ChatRoom\Core\Helpers\User;
use ChatRoom\Core\Helpers\Helpers;
use ChatRoom\Core\Database\SqlLite;

class UserController
{
    private $validateUsername;
    /**
     * 系统保留名称
     */
    private $reservedNames;
    public $Helpers;
    private $userHelpers;

    /**
     * UserController
     *
     */
    public function __construct()
    {
        $this->validateUsername = new User;
        /**
         * ！警告！
         * ！请勿修改此处保留字符，可能会出现意想不到的情况！
         */
        $this->reservedNames = ['admin', 'system', 'root'];

        $this->Helpers = new Helpers;
        $this->userHelpers = new User;
    }

    /**
     * @param string $username
     * @param string $password
     * @param string $confirmPassword
     */
    public function register($username, $password, $confirmPassword)
    {
        try {
            // 验证用户名
            if (!$this->validateUsername->validateUsername($username)) {
                return $this->Helpers->jsonResponse('用户名不合法，必须长度在3到20字符之间和不能有中文和特殊字符', 400);
            }

            // 验证保留名称
            if (in_array(
                $username,
                $this->reservedNames
            )) {
                return $this->Helpers->jsonResponse("用户名不合法，系统保留名称", 400);
            }

            // 验证密码和确认密码是否一致
            if ($password !== $confirmPassword) {
                return $this->Helpers->jsonResponse('确认密码和密码不一致', 400);
            }

            // 检查用户名是否重复
            if ($this->validateUsername->isUsernameTaken($username)) {
                return $this->Helpers->jsonResponse('用户名已被注册', 400);
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $db = SqlLite::getInstance()->getConnection();
            $stmt = $db->prepare('INSERT INTO users (username, password, created_at, register_ip) VALUES (?, ?, ?, ?)');
            $isSuccessful = $stmt->execute([$username, $passwordHash, date('Y-m-d H:i:s'), $this->userHelpers->getIp()]);

            if ($isSuccessful) {

                $_SESSION['userinfo'] = $this->validateUsername->getUserInfo($username);
                // 插入回归消息
                $this->insertSystemMessage('admin', "欢迎{$username}来到聊天室！", 'system');
                return $this->Helpers->jsonResponse('注册成功', 200);
            } else {
                return $this->Helpers->jsonResponse('注册失败，请重试', 500);
            }
        } catch (PDOException $e) {
            handleException($e);
            return $this->Helpers->jsonResponse("内部服务器错误。请联系管理员。", 500);
        }
    }

    /**
     * @param string $username
     * @param string $password
     */
    public function login($username, $password)
    {
        try {
            // 验证用户名
            if (!$this->validateUsername->validateUsername($username)) {
                return $this->Helpers->jsonResponse("用户名不合法，必须长度在3到20字符之间和不能有中文和特殊字符", 400);
            }
            // 验证保留名称
            if (in_array($username, $this->reservedNames)) {
                return $this->Helpers->jsonResponse("用户名不合法，系统保留名称", 400);
            }

            // 使用getUserInfo来获取用户信息
            $user = $this->validateUsername->getUserInfo($username);

            if (empty($user)) {
                return $this->Helpers->jsonResponse('用户名不存在', 400);
            }
            if (!password_verify($password, $user['password'])) {
                return $this->Helpers->jsonResponse('密码错误', 400);
            }

            // 权限验证通过后，移除密码信息并保存其他用户信息到SESSION
            unset($user['password']); // 移除密码信息
            $_SESSION['userinfo'] = $user; // 存储用户信息到会话

            // 插入回归消息
            $this->insertSystemMessage('admin', "欢迎{$username}来到聊天室！", 'system');

            return $this->Helpers->jsonResponse('登录成功', 200);
        } catch (Exception $e) {
            handleException($e);
            return $this->Helpers->jsonResponse("内部服务器错误。请联系管理员。", 500);
        }
    }

    /**
     * 插入系统消息到聊天记录
     *
     * @param string $user_name
     * @param string $message
     * @param string $type
     * @return void
     */
    private function insertSystemMessage($user_name, $message, $type)
    {
        try {
            $db = SqlLite::getInstance()->getConnection();
            $stmt = $db->prepare('INSERT INTO messages (user_name, content, type, created_at) VALUES (?, ?, ?, ?)');
            $stmt->execute([$user_name, $message, $type, date('Y-m-d H:i:s')]);
        } catch (PDOException $e) {
            handleException($e);
        }
    }
}
