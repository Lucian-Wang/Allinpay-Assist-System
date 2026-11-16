# 通用支付API接口文档

示例域名 `pay.example.com` 和通道编号均为占位值，请替换为你实际部署的域名和后台生成的通道标识。

**接口基础地址**：`https://pay.example.com/commonpay/api/`

## 1. 创建支付订单

- **接口**：`POST /pay.php`
- **请求类型**：application/json

### 请求参数

| 参数名         | 类型   | 必填 | 说明                     |
| -------------- | ------ | ---- | ------------------------ |
| channel        | string | 是   | 通道标识（后台获取）     |
| pay_order_no   | string | 是   | 平台支付订单号（全局唯一）|
| amount         | string | 是   | 金额（元，字符串）       |
| subject        | string | 是   | 商品名称                 |
| user_id        | string | 否   | 业务用户ID               |
| biz_sys        | string | 否   | 业务系统标识             |
| biz_order_no   | string | 否   | 业务订单号               |
| notify_url     | string | 否   | 后台回调通知地址         |
| return_url     | string | 否   | 支付完成后跳转地址       |
| extra          | string | 否   | 业务扩展参数             |

### 请求示例

```json
{
  "channel": "BRLWJ6S0",
  "pay_order_no": "BIZ202506090012371355",
  "amount": "0.01",
  "subject": "测试商品-091937",
  "notify_url": "https://webhook.site/xxxxxx",
  "return_url": "https://www.baidu.com/"
}
```

### 返回参数

| 字段        | 类型   | 说明                       |
| ----------- | ------ | -------------------------- |
| code        | int    | 0表示成功，非零为错误      |
| msg         | string | 信息说明                   |
| data        | object | 具体返回数据               |
| data.pay_url | string | 支付跳转链接               |
| data.pay_order_no | string | 订单号               |

### 返回示例

```json
{
  "code": 0,
  "msg": "ok",
  "data": {
    "pay_url": "https://pay.example.com/commonpay/pay.php?channel=BRLWJ6S0&pay_order_no=BIZ202506090012371355&amount=0.01&subject=测试商品-091937",
    "pay_order_no": "BIZ202506090012371355"
  }
}
```

---

## 2. 查询订单状态

- **接口**：`GET /order.php?pay_order_no=xxx`
- **请求类型**：application/json

### 请求参数

| 参数名       | 类型   | 必填 | 说明         |
| ------------ | ------ | ---- | ------------|
| pay_order_no | string | 是   | 支付订单号   |

### 返回参数

| 字段         | 类型   | 说明               |
| ------------ | ------ | ------------------|
| code         | int    | 0成功，非零为错误 |
| msg          | string | 信息说明           |
| data         | object | 具体返回数据       |
| data.status  | string | pending, paid, fail, closed |
| data.amount  | string | 金额，单位元       |
| data.subject | string | 商品名             |
| data.paid_at | string | 支付成功时间       |
| data.allinpay_trxid | string | 通联交易号   |

### 返回示例

```json
{
  "code": 0,
  "msg": "ok",
  "data": {
    "pay_order_no": "BIZ202506090012371355",
    "status": "paid",
    "amount": "0.01",
    "subject": "测试商品-091937",
    "paid_at": "2025-06-09 12:01:01",
    "allinpay_trxid": "20250609xxxxxx"
  }
}
```

---

## 3. 支付回调说明

- 支付成功后，系统会POST通知到你在下单时传的notify_url。
- POST参数如：

```json
{
  "pay_order_no": "BIZ202506090012371355",
  "user_id": "testuser",
  "amount": "0.01",
  "status": "paid",
  "paid_at": "2025-06-09 12:01:01",
  "biz_sys": "apitest",
  "biz_order_no": "BIZ202506090012371354",
  "extra": "xxx"
}
```

**建议用 [webhook.site](https://webhook.site/) 这类平台测试notify_url接收效果。**

---

## 4. 代码调用示例

### PHP创建订单

```php
$url = "https://pay.example.com/commonpay/api/pay.php";
$data = [
  "channel" => "BRLWJ6S0",
  "pay_order_no" => "BIZ202506090012371355",
  "amount" => "0.01",
  "subject" => "测试商品-091937"
];
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$resp = curl_exec($ch);
curl_close($ch);
echo $resp;
```

### PHP查询订单

```php
$url = "https://pay.example.com/commonpay/api/order.php?pay_order_no=BIZ202506090012371355";
$resp = file_get_contents($url);
echo $resp;
```

### cURL 命令行示例

```sh
curl -X POST 'https://pay.example.com/commonpay/api/pay.php' \
  -H 'Content-Type: application/json' \
  -d '{"channel":"BRLWJ6S0","pay_order_no":"BIZ202506090012371355","amount":"0.01","subject":"测试商品-091937"}'
```

```sh
curl 'https://pay.example.com/commonpay/api/order.php?pay_order_no=BIZ202506090012371355'
```

---

## 5. 常见错误码

| code | msg              | 说明           |
|------|------------------|----------------|
| 0    | ok               | 成功           |
| 1    | 参数错误         | 缺少/格式不对  |
| 2    | 通道不存在       |                |
| 3    | 订单不存在       |                |
| 4    | 订单已支付       |                |

---

## 6. 注意事项

- amount单位为元，字符串格式
- pay_order_no务必全局唯一，重复会失败
- notify_url、return_url建议公网可访问
- 推荐notify_url用webhook.site调试
- 返回均为application/json格式

---
