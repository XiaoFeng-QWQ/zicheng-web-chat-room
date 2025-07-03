<?php

namespace ChatRoom\Core\Helpers;

/**
 * HTML 生成辅助类
 * 
 * 提供安全的 HTML 生成方法，支持链式调用
 */
class HtmlBuilder
{
    /**
     * @var string 当前生成的 HTML 内容
     */
    protected $html = '';

    /**
     * 开始构建 HTML 文档
     * 
     * @param string $doctype 文档类型
     * @return $this
     */
    public function startDocument($doctype = '<!DOCTYPE html>')
    {
        $this->html = $doctype . "\n";
        return $this;
    }

    /**
     * 开始 HTML 标签
     * 
     * @param string $tag 标签名
     * @param array $attributes 属性数组
     * @param bool $selfClosing 是否自闭合
     * @return $this
     */
    public function beginTag($tag, $attributes = [], $selfClosing = false)
    {
        $this->html .= '<' . $tag . $this->buildAttributes($attributes);
        $this->html .= $selfClosing ? ' />' : '>';
        return $this;
    }

    /**
     * 结束 HTML 标签
     * 
     * @param string $tag 标签名
     * @return $this
     */
    public function endTag($tag)
    {
        $this->html .= '</' . $tag . '>';
        return $this;
    }

    /**
     * 添加完整标签（包含内容）
     * 
     * @param string $tag 标签名
     * @param string $content 内容
     * @param array $attributes 属性数组
     * @return $this
     */
    public function tag($tag, $content = '', $attributes = [])
    {
        $this->beginTag($tag, $attributes);
        $this->text($content);
        $this->endTag($tag);
        return $this;
    }

    /**
     * 添加文本内容（自动转义）
     * 
     * @param string $text 文本内容
     * @return $this
     */
    public function text($text)
    {
        $this->html .= htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return $this;
    }

    /**
     * 添加原始 HTML（不转义）
     * 
     * @param string $html HTML 内容
     * @return $this
     */
    public function raw($html)
    {
        $this->html .= $html;
        return $this;
    }

    /**
     * 添加换行
     * 
     * @param int $count 换行数量
     * @return $this
     */
    public function newLine($count = 1)
    {
        $this->html .= str_repeat("\n", $count);
        return $this;
    }

    /**
     * 构建属性字符串
     * 
     * @param array $attributes 属性数组
     * @return string
     */
    protected function buildAttributes($attributes)
    {
        $result = '';
        foreach ($attributes as $name => $value) {
            if ($value === null || $value === false) {
                continue;
            }

            if ($value === true) {
                $result .= ' ' . $name;
            } else {
                $result .= ' ' . $name . '="' . htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
            }
        }
        return $result;
    }

    /**
     * 获取生成的 HTML
     * 
     * @return string
     */
    public function getHtml()
    {
        return $this->html;
    }

    /**
     * 清空当前 HTML 内容
     * 
     * @return $this
     */
    public function reset()
    {
        $this->html = '';
        return $this;
    }

    /**
     * 输出 HTML
     */
    public function render()
    {
        echo $this->html;
    }

    /******************** 常用快捷方法 ********************/

    /**
     * 生成链接
     * 
     * @param string $url 链接地址
     * @param string $text 链接文本
     * @param array $attributes 额外属性
     * @return $this
     */
    public function link($url, $text, $attributes = [])
    {
        $attributes['href'] = $url;
        return $this->tag('a', $text, $attributes);
    }

    /**
     * 生成图片
     * 
     * @param string $src 图片地址
     * @param string $alt 替代文本
     * @param array $attributes 额外属性
     * @return $this
     */
    public function image($src, $alt = '', $attributes = [])
    {
        $attributes['src'] = $src;
        $attributes['alt'] = $alt;
        return $this->beginTag('img', $attributes, true);
    }

    /**
     * 生成表单
     * 
     * @param string $action 表单提交地址
     * @param string $method 提交方法
     * @param array $attributes 额外属性
     * @return $this
     */
    public function beginForm($action = '', $method = 'post', $attributes = [])
    {
        $attributes['action'] = $action;
        $attributes['method'] = $method;
        return $this->beginTag('form', $attributes);
    }

    /**
     * 结束表单
     * 
     * @return $this
     */
    public function endForm()
    {
        return $this->endTag('form');
    }

    /**
     * 生成输入框
     * 
     * @param string $type 输入类型
     * @param string $name 名称
     * @param string $value 值
     * @param array $attributes 额外属性
     * @return $this
     */
    public function input($type, $name, $value = '', $attributes = [])
    {
        $attributes['type'] = $type;
        $attributes['name'] = $name;
        $attributes['value'] = $value;
        return $this->beginTag('input', $attributes, true);
    }

    public function meta($attributes)
    {
        return $this->beginTag('meta', $attributes, true);
    }

    public function stylesheet($url, $attributes = [])
    {
        $attributes['href'] = $url;
        $attributes['rel'] = 'stylesheet';
        return $this->beginTag('link', $attributes, true);
    }

    public function script($src, $attributes = [])
    {
        $attributes['src'] = $src;
        return $this->beginTag('script', $attributes, false)
            ->endTag('script');
    }
}
