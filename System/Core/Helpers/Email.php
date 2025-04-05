<?php

namespace ChatRoom\Core\Helpers;

use ChatRoom\Core\Config\App;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Email
{
    private $mail;

    // 构造函数，初始化 PHPMailer 实例
    public function __construct()
    {
        $this->mail = new PHPMailer(true);
    }

    // 配置 SMTP 设置
    private function configureSMTP()
    {
        $config = new App;
        if (defined('FRAMEWORK_DEBUG') && FRAMEWORK_DEBUG) {
            $this->mail->SMTPDebug = \PHPMailer\PHPMailer\SMTP::DEBUG_SERVER;
        }
        $this->mail->isSMTP();
        $this->mail->SMTPAuth = true;
        $this->mail->Host = $config->email['smtp']['host'];
        $this->mail->Port = $config->email['smtp']['port'];
        $this->mail->Username = $config->email['smtp']['username'];
        $this->mail->Password = $config->email['smtp']['password'];
        $this->mail->SMTPSecure = $config->email['smtp']['secure'];
    }

    /**
     * 发送邮件
     *
     * @param [type] $from
     * @param [type] $fromName
     * @param [type] $to
     * @param [type] $bcc
     * @param [type] $subject
     * @param [type] $body
     * @return bool
     */
    public function send($from, $fromName, $bcc, $subject, $body): bool
    {
        try {
            // 配置 SMTP
            $this->configureSMTP();

            // 设置发件人邮箱
            $this->mail->setFrom($from, $fromName);

            // 添加密件抄送（BCC）
            if (is_array($bcc)) {
                foreach ($bcc as $recipient) {
                    if (isset($recipient['email'])) {
                        $this->mail->addBCC($recipient['email']);
                    }
                }
            } else {
                $this->mail->addBCC($bcc); // 如果只有一个密件抄送地址
            }

            // 设置邮件内容格式为 HTML
            $this->mail->isHTML(true);

            // 设置邮件的编码为 UTF-8
            $this->mail->CharSet = 'UTF-8';  // 明确指定字符集

            // 设置邮件主题和正文内容
            $this->mail->Subject = $subject;
            $this->mail->Body    = $body;
            // 设置纯文本版本的邮件内容，以便不支持 HTML 的客户端使用
            $this->mail->AltBody = strip_tags($body);

            // 发送邮件
            return $this->mail->send();
        } catch (\Exception) {
            throw new \Exception("邮件发送失败。Mailer 错误: {$this->mail->ErrorInfo}");
        }
    }
}
