# V1 API文档

## 返回结构

```json
{
  "APIVersion": "1.1.0.0",
  "code": "int",
  "message": "string",
  "data": []
}
```

### *除非特别说明*：

- **API不存在**：返回 `code` 400。
- **请求成功**：返回 `code` 200。
- **Token未传递**：返回 `code` 401。
- **返回数据**：所有数据均在 `data` 数组内。
- **服务器内部错误**：返回 `code` 500。
- **API名称不符合字母和数字格式**：返回 `code` 403。
- **服务器无法根据客户端请求的内容特性完成请求**：返回 `code` 406。

---

## 必须项

### 传递的数据

- 除了 `user captcha` 外，所有其他数据均需通过 `$_POST['token']` 进行传递。

---

## 聊天
如果聊天命令执行失败返回 `code: 403` message并返回出错原因。

### 获取消息接口：`/api/v1/chat/get`

#### 可选参数：

- ``GET`` offset 偏移量
- ``GET`` limit  限制条数
```php
(int)$_GET['offset'] : 0;
(int)$_GET['limit'] : 10;
```

#### 返回格式：
```json
{
  "total":  int,
  "onlineUsers": {
    "1": {
      "user_name": "XiaoFeng",
      "avatar_url": null,
      "last_time": 1737557434
    }
  },
  "messages": [
    {
      "id": 1,
      "type": "system",
      "content": "欢迎XiaoFeng来到聊天室！",
      "user_name": "system",
      "user_group_id": null,
      "created_at": "2024-08-07 18:37:28",
      "avatar_url": null,
      "group_name": null
    }
  ]
}
```

---

#### 说明

- **`total`**：消息的总数。
- **`onlineUsers`**：当前在线的用户信息，以用户 ID 为键，用户信息为值。
- **`messages`**：消息数组，每条消息包含以下字段：
  - `id`：消息的唯一标识。
  - `type`：消息类型（`system | user | user.markdown`）。
  - `content`：消息内容。
  - `user_name`：消息发送者用户名。
  - `user_group_id`：用户所属组的ID。
  - `created_at`：消息的创建时间。
  - `avatar_url`：用户头像的URL（为空时返回NULL）。
  - `group_name`：用户所属组的名称。