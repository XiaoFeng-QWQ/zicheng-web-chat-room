<?php

namespace ChatRoom\Core\Controller;

use Exception;
use Throwable;

class ChatCommandVoteController
{
    private $currentVote = null;  // 当前的投票
    private $voters = [];  // 记录已投票的用户
    private $filePath = FRAMEWORK_DIR . '/Writable/chat.command.vote.json';  // 存储投票数据的文件路径

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->loadVoteData();  // 加载已保存的投票数据
    }

    /**
     * 发起投票
     * 
     * @param string $topic 投票主题
     * @param string $endTime 结束时间
     * @param mixed ...$options 投票选项
     * @return string 投票的开始信息
     */
    public function 发起投票($topic, ...$options)
    {
        if ($this->currentVote === null) {
            if (count($options) < 2) {
                return '投票至少需要两个选项！';
            }
            // 创建新的投票
            $this->currentVote = [
                'topic' => $topic,
                'options' => array_combine(range(1, count($options)), $options),
                'votes' => array_fill_keys(range(1, count($options)), 0),
                'startTime' => time(),
                'endTime' => time() + 86400,  // 设置投票24小时有效
            ];
            // 清空之前的投票记录
            $this->voters = [];
            // 保存到文件
            $this->saveVoteData();
            $optionsText = '';
            foreach ($this->currentVote['options'] as $key => $option) {
                $optionsText .= "{$key}. {$option}<br>";
            }
            return "投票主题: {$topic}<br>选项:<br>{$optionsText}<br>请使用 `/投票 [编号]` 参与投票。";
        } else {
            $results = "当前已有进行中的投票了！投票主题: {$this->currentVote['topic']}<br>结果:<br>";
            $count = 0;
            foreach ($this->currentVote['options'] as $key => $option) {
                $count++;
                $results .= "{$count}.{$option}<br>";
            }
            $results .= '<hr>发起时间:' . date('Y-m-d H:m:s', $this->currentVote['startTime']) . '<br>结束时间' . date('Y-m-d H:m:s', $this->currentVote['endTime']) . '<br>请使用 `/投票 [编号]` 参与投票。';
            return $results;;
        }
    }

    /**
     * 投票
     * 
     * @param int $option 选择的选项编号
     * @return string 投票结果
     */
    public function 投票($userInfo,$option)
    {
        $userId = $userInfo['user_id'];
        if ($this->currentVote === null) {
            return '当前没有进行中的投票。';
        }
        // 检查是否超时
        if (time() > $this->currentVote['endTime']) {
            return '投票已结束。';
        }
        // 检查选项有效性
        if (!isset($this->currentVote['options'][$option])) {
            return '无效的选项编号！';
        }
        // 检查是否已投过票
        if (isset($this->voters[$userId])) {
            return '您已经投过票，不能重复投票。';
        }
        // 记录投票者
        $this->voters[$userId] = $option;
        $this->currentVote['votes'][$option]++;
        // 保存投票数据
        $this->saveVoteData();
        return "投票成功！当前票数:<br>" . $this->显示投票结果();
    }

    /**
     * 显示投票结果
     * 
     * @return string 投票结果
     */
    public function 显示投票结果()
    {
        if ($this->currentVote === null) {
            return '当前没有进行中的投票。';
        }
        $results = "投票主题: {$this->currentVote['topic']}<br>结果:<br>";
        foreach ($this->currentVote['options'] as $key => $option) {
            $results .= "{$option}: {$this->currentVote['votes'][$key]} 票<br>";
        }
        $results .= '<hr>发起时间:' . date('Y-m-d H:m:s', $this->currentVote['startTime']) . '<br>结束时间' . date('Y-m-d H:m:s', $this->currentVote['endTime']);
        return $results;
    }

    /**
     * 取消投票
     * 
     * @param int $userId 用户 ID
     * @return string 取消投票的结果
     */
    public function 取消投票($userId)
    {
        if ($this->currentVote === null) {
            return '当前没有进行中的投票。';
        }
        // 检查用户是否投票
        if (!isset($this->voters[$userId])) {
            return '您还没有投票。';
        }
        // 获取用户之前的投票选项
        $previousOption = $this->voters[$userId];
        // 减少该选项的票数
        $this->currentVote['votes'][$previousOption]--;
        // 删除该用户的投票记录
        unset($this->voters[$userId]);
        // 保存投票数据
        $this->saveVoteData();
        return "您的投票已取消。当前票数:<br>" . $this->显示投票结果();
    }

    /**
     * 结束当前投票
     * 
     * @return string 结束投票的结果
     */
    public function 结束投票()
    {
        if ($this->currentVote === null) {
            return '当前没有进行中的投票。';
        }
        // 结束投票
        $this->currentVote = null;
        $this->voters = null;
        // 保存投票数据
        $this->saveVoteData();
        return '投票已结束。';
    }

    /**
     * 获取投票剩余时间
     * 
     * @return string 剩余时间信息
     */
    public function 剩余时间()
    {
        if ($this->currentVote === null) {
            return '当前没有进行中的投票。';
        }
        $remainingTime = $this->currentVote['endTime'] - time();
        if ($remainingTime <= 0) {
            return '投票已经结束。';
        }
        $hours = floor($remainingTime / 3600);
        $minutes = floor(($remainingTime % 3600) / 60);
        return "投票还剩下 {$hours} 小时 {$minutes} 分钟。";
    }

    /**
     * 加载投票数据
     */
    private function loadVoteData()
    {
        if (file_exists($this->filePath)) {
            $data = file_get_contents($this->filePath);
            $voteData = json_decode($data, true);
            if (isset($voteData['currentVote'])) {
                $this->currentVote = $voteData['currentVote'];
                $this->voters = $voteData['voters'];
            }
        }
    }

    /**
     * 保存投票数据
     */
    private function saveVoteData()
    {
        try {
            $data = [
                'currentVote' => $this->currentVote,
                'voters' => $this->voters,
            ];
            file_put_contents($this->filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } catch (Throwable $e) {
            // 处理异常并抛出原始异常信息
            throw new Exception('保存投票数据时发生错误:' . $e);
        }
    }
}
