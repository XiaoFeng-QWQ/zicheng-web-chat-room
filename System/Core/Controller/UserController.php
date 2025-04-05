<?php


namespace ChatRoom\Core\Controller;

use PDOException;
use ChatRoom\Core\Helpers\User;
use ChatRoom\Core\Database\Base;
use ChatRoom\Core\Helpers\Helpers;
use ChatRoom\Core\Modules\TokenManager;
use ChatRoom\Core\Controller\ChatController;

class UserController
{
    private $db;
    public $Helpers;
    private $chatController;
    private $tokenManager;
    private $reservedNames;
    private $userHelpers;

    // 状态常量
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
        $this->userHelpers = new User;
        /**
         * ！警告！
         * ！请勿修改此处保留字符，可能会出现意想不到的情况！
         */
        $this->reservedNames = ['system', 'root', 'admin'];

        $this->db = Base::getInstance()->getConnection();
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
            if (!$this->userHelpers->validateUsername($username)) {
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
            if ($this->userHelpers->isUsernameTaken($username)) {
                return $this->Helpers->jsonResponse(400, '用户名已被注册');
            }
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare('INSERT INTO users (username, password, created_at, register_ip) VALUES (?, ?, ?, ?)');
            $isSuccessful = $stmt->execute([$username, $passwordHash, date('Y-m-d H:i:s'), $this->userHelpers->getIp()]);

            if ($isSuccessful) {
                $this->updateLoginInfo($this->userHelpers->getUserInfo($username));
                $this->chatController->insertSystemMessage('system', "欢迎新用户 $username 来到聊天室！", 'system');
                return $this->Helpers->jsonResponse(200, '注册成功');
            } else {
                return $this->Helpers->jsonResponse(500, '注册失败，请重试');
            }
        } catch (PDOException $e) {
            throw new PDOException('注册发生错误:' . $e->getMessage());
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
            if (!$this->userHelpers->validateUsername($username)) {
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
            $user = $this->userHelpers->getUserInfo($username);
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
            throw new PDOException('登录发生错误:' . $e->getMessage());
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
