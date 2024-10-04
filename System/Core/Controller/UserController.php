<?php


namespace ChatRoom\Core\Controller;

use Exception;
use PDOException;
use Monolog\Logger;
use ChatRoom\Core\Helpers\User;
use ChatRoom\Core\Helpers\Helpers;
use ChatRoom\Core\Database\SqlLite;
use ChatRoom\Core\Auth\TokenManager;

class UserController
{

    public $Helpers;
    private $userHelpers;
    private $tokenManager;
    private $reservedNames;
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
        $this->reservedNames = ['admin', 'system', 'root'];

        $this->Helpers = new Helpers;
        $this->userHelpers = new User;
        $this->tokenManager = new TokenManager;
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

                $this->updateLoginInfo($this->validateUsername->getUserInfo($username));

                $this->insertSystemMessage('system', "欢迎新用户 $username 来到聊天室！", 'system');

                return $this->Helpers->jsonResponse('注册成功', 200);
            } else {
                return $this->Helpers->jsonResponse('注册失败，请重试', 500);
            }
        } catch (PDOException $e) {
            HandleException($e);
            return $this->Helpers->jsonResponse("内部服务器错误。请联系管理员。", 500);
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
                    return $this->Helpers->jsonResponse("用户名不合法，必须长度在3到20字符之间和不能有中文和特殊字符", 400);
                }
            }
            // 验证保留名称
            if (in_array($username, $this->reservedNames)) {
                if ($return) {
                    return "用户名不合法，系统保留名称";
                } else {
                    return $this->Helpers->jsonResponse("用户名不合法，系统保留名称", 400);
                }
            }
            // 使用getUserInfo来获取用户信息
            $user = $this->validateUsername->getUserInfo($username);
            if (empty($user)) {
                if ($return) {
                    return "用户名不存在";
                } else {
                    return $this->Helpers->jsonResponse('用户名不存在', 400);
                }
            }
            if (!password_verify($password, $user['password'])) {
                if ($return) {
                    return "密码错误";
                } else {
                    return $this->Helpers->jsonResponse('密码错误', 400);
                }
            }
            // 更新用户登录信息
            $this->updateLoginInfo($user);
            if ($return) {
                return true;
            } else {
                return $this->Helpers->jsonResponse('登录成功', 200);
            }
        } catch (Exception $e) {
            throw new Exception($e);
            if ($return) {
                return "内部服务器错误。请联系管理员。";
            } else {
                return $this->Helpers->jsonResponse("内部服务器错误。请联系管理员。", 500);
            }
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
            HandleException($e);
        }
    }

    /**
     * 更新登录信息
     * @param array $user 用户信息数组
     * @return void
     */
    private function updateLoginInfo(array $user)
    {
        // 移除无用信息
        unset($user['email']);
        unset($user['password']);
        unset($user['register_ip']);
        unset($user['admin_login_token']);

        $user['user_login_token'] = $this->tokenManager->generateToken($user['user_id'], '+1 year');
        $_SESSION['user_login_info'] = $user; // 存储用户信息到会话
        setcookie(
            'user_login_info',
            json_encode($user),
            [
                'expires' => time() + 86400 * 365,
                'path' => '/'
            ]
        );
        unset($_SESSION['captcha']);
    }
}
