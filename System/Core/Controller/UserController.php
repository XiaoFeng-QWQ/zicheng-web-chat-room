<?php


namespace ChatRoom\Core\Controller;

use PDOException;
use Monolog\Logger;
use ChatRoom\Core\Helpers\User;
use ChatRoom\Core\Helpers\Helpers;
use ChatRoom\Core\Database\SqlLite;
use ChatRoom\Core\Modules\TokenManager;
use ChatRoom\Core\Controller\ChatController;

class UserController
{

    private $event;
    public $Helpers;
    private $userHelpers;
    private $tokenManager;
    private $reservedNames;
    private $chatController;
    /**
     * 系统保留名称
     */
    private $validateUsername;

    // 状态常量
    const LOG_LEVEL = Logger::ERROR;
    const CAPTCHA_ERROR = '验证码错误';
    const INVALID_METHOD_MESSAGE = '无效的方法。';
    const METHOD_NOT_PROVIDED_MESSAGE = '方法未提供。';
    const DISABLE_USER_REGIDTRATION = '新用户注册已禁用';
    const INVALID_REQUEST_METHOD_MESSAGE = '无效的请求方法。';

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
        $this->reservedNames = ['system', 'root', 'admin'];

        $this->event = new Events;
        $this->Helpers = new Helpers;
        $this->userHelpers = new User;
        $this->tokenManager = new TokenManager;
        $this->chatController = new ChatController;
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
                return $this->Helpers->jsonResponse(400, '用户名不合法，必须长度在3到20字符之间和不能有中文和特殊字符');
            }

            // 验证保留名称
            if (in_array(
                $username,
                $this->reservedNames
            )) {
                return $this->Helpers->jsonResponse(400, "用户名不合法，系统保留名称");
            }

            // 验证密码和确认密码是否一致
            if ($password !== $confirmPassword) {
                return $this->Helpers->jsonResponse(400, '确认密码和密码不一致');
            }

            // 检查用户名是否重复
            if ($this->validateUsername->isUsernameTaken($username)) {
                return $this->Helpers->jsonResponse(400, '用户名已被注册');
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $db = SqlLite::getInstance()->getConnection();
            $stmt = $db->prepare('INSERT INTO users (username, password, created_at, register_ip) VALUES (?, ?, ?, ?)');
            $isSuccessful = $stmt->execute([$username, $passwordHash, date('Y-m-d H:i:s'), $this->userHelpers->getIp()]);

            if ($isSuccessful) {
                $this->updateLoginInfo($this->validateUsername->getUserInfo($username));
                $this->chatController->insertSystemMessage('system', "欢迎新用户 $username 来到聊天室！", 'system');
                return $this->Helpers->jsonResponse(200, '注册成功');
            } else {
                return $this->Helpers->jsonResponse(500, '注册失败，请重试');
            }
        } catch (PDOException $e) {
            throw new PDOException('注册发生错误:' . $e);
            return $this->Helpers->jsonResponse(500, "内部服务器错误。请联系管理员。");
        }
    }

    /**
     * @param string $username
     * @param string $password 明文
     * @param bool $return 是否返回纯文本
     */
    public function login($username, $password, $return = false)
    {
        try {
            // 验证用户名
            if (!$this->validateUsername->validateUsername($username)) {
                if ($return) {
                    return "用户名不合法，必须长度在3到20字符之间和不能有中文和特殊字符";
                } else {
                    return $this->Helpers->jsonResponse(400, "用户名不合法，必须长度在3到20字符之间和不能有中文和特殊字符");
                }
            }
            // 验证保留名称
            if (in_array($username, $this->reservedNames)) {
                if ($return) {
                    return "用户名不合法，系统保留名称";
                } else {
                    return $this->Helpers->jsonResponse(400, "用户名不合法，系统保留名称",);
                }
            }
            // 使用getUserInfo来获取用户信息
            $user = $this->validateUsername->getUserInfo($username);
            if (empty($user)) {
                if ($return) {
                    return "用户名不存在";
                } else {
                    return $this->Helpers->jsonResponse(400, '用户名不存在');
                }
            }
            if (!password_verify($password, $user['password'])) {
                if ($return) {
                    return "密码错误";
                } else {
                    return $this->Helpers->jsonResponse(400, '密码错误');
                }
            }
            if ($return) {
                return true;
            } else {
                return $this->Helpers->jsonResponse(200, '登录成功', $this->updateLoginInfo($user));
            }
        } catch (PDOException $e) {
            throw new PDOException('登录发生错误:' . $e);
            if ($return) {
                return "内部服务器错误。请联系管理员。";
            } else {
                return $this->Helpers->jsonResponse(500, "内部服务器错误。请联系管理员。");
            }
        }
    }

    /**
     * 更新登录信息
     * @param array $user 用户信息数组
     * @return array
     */
    private function updateLoginInfo(array $user)
    {
        // 移除无用信息
        unset($user['password']);
        $user['token'] = $this->tokenManager->generateToken($user['user_id'], '+1 year');
        setcookie(
            'user_login_info',
            json_encode($user),
            [
                'expires' => time() + 86400 * 365,
                'path' => '/'
            ]
        );
        unset($_SESSION['captcha']);
        return $user;
    }
}
