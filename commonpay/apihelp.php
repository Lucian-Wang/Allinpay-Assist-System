<?php
$page_title = '通用支付API接口文档';
require_once '../header.php';
require_login();
$user = current_user();
?>
<style>
body {
    background: #f7fafd;
    font-family: 'Segoe UI', 'PingFang SC', 'Arial', sans-serif;
    margin: 0;
    padding: 0;
}
.content-card {
    max-width: 900px;
    margin: 48px auto;
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 8px 36px rgba(44,104,255,0.10);
    padding: 24px 32px;
}
.card-header {
    margin-bottom: 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.card-title {
    font-size: 22px;
    font-weight: 700;
    color: #3183f4;
}
.card-actions a {
    margin-left: 18px;
    text-decoration: none;
    color: #3183f4;
    font-size: 15px;
}
.table {
    border-collapse: collapse;
    width: 100%;
    margin-bottom: 20px;
}
.table th, .table td {
    border: 1px solid #e3e9f7;
    padding: 8px 12px;
    font-size: 15px;
    text-align: left;
}
pre, code {
    background: #f7fafd;
    border: 1px solid #e3e9f7;
    padding: 7px 11px;
    font-size: 14px;
    border-radius: 5px;
    overflow-x: auto;
    color: #2d8cf0;
    line-height: 1.6;
}
pre {
    margin: 6px 0 14px 0;
}
ol, ul {
    margin-left: 22px;
}
@media (max-width: 700px) {
    .content-card {padding: 12px 5vw;}
    .card-header {flex-direction: column; gap: 8px;}
}
</style>
<div class="content-card">
    <div class="card-header">
        <div class="card-title">通用支付API接口文档</div>
        <div class="card-actions">
            <a href="/commonpay/help.php">H5入口说明</a>
            <a href="/commonpay/index.php">通道管理</a>
            <a href="/users/index.php">用户中心</a>
        </div>
    </div>
    <div style="padding:24px 16px;">

        <h2 style="color:#3183f4;">API基础信息</h2>
        <div style="background:#f7faff;border:1px solid #e3e9f7;padding:10px 16px;word-break:break-all;margin-bottom:18px;">
            <b>API基础地址：</b> https://allinpay.zhuli.pro/commonpay/api/<br>
            <b>返回格式：</b> application/json
        </div>

        <h3>1. 创建支付订单 <span style="color:#888;font-size:14px;">(POST /pay.php)</span></h3>
        <table class="table" style="width:100%;background:#fff;">
            <thead>
                <tr>
                    <th>参数名</th>
                    <th>必填</th>
                    <th>类型</th>
                    <th>说明</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>channel</td><td>是</td><td>string</td><td>通道标识，后台“通道管理”自动生成</td></tr>
                <tr><td>pay_order_no</td><td>是</td><td>string</td><td>平台支付订单号，需全局唯一</td></tr>
                <tr><td>amount</td><td>是</td><td>string</td><td>金额(元)，如0.01</td></tr>
                <tr><td>subject</td><td>是</td><td>string</td><td>商品名称</td></tr>
                <tr><td>user_id</td><td>否</td><td>string</td><td>业务用户ID，可选</td></tr>
                <tr><td>biz_sys</td><td>否</td><td>string</td><td>业务系统标识，可选</td></tr>
                <tr><td>biz_order_no</td><td>否</td><td>string</td><td>业务订单号，可选</td></tr>
                <tr><td>notify_url</td><td>否</td><td>string</td><td>支付后回调通知地址（如需业务系统接收支付结果）</td></tr>
                <tr><td>return_url</td><td>否</td><td>string</td><td>支付完成后前端跳转地址</td></tr>
                <tr><td>extra</td><td>否</td><td>string</td><td>业务扩展参数</td></tr>
            </tbody>
        </table>

        <div class="label" style="color:#3183f4;font-weight:600;margin:10px 0 2px 0;">请求示例（JSON）</div>
        <pre>
{
    "channel": "BRLWJ6S0",
    "pay_order_no": "BIZ202506090012371355",
    "amount": "0.01",
    "subject": "测试商品-091937",
    "notify_url": "https://webhook.site/xxxxxx",
    "return_url": "https://www.baidu.com/"
}
        </pre>
        <div class="label" style="color:#3183f4;font-weight:600;margin:10px 0 2px 0;">返回示例</div>
        <pre>
{
    "code": 0,
    "msg": "ok",
    "data": {
        "pay_url": "https://allinpay.zhuli.pro/commonpay/pay.php?channel=BRLWJ6S0&pay_order_no=BIZ202506090012371355&amount=0.01&subject=测试商品-091937",
        "pay_order_no": "BIZ202506090012371355"
    }
}
        </pre>

        <h3 style="margin-top:38px;">2. 查询订单状态 <span style="color:#888;font-size:14px;">(GET /order.php)</span></h3>
        <table class="table" style="width:100%;background:#fff;">
            <thead>
                <tr>
                    <th>参数名</th>
                    <th>必填</th>
                    <th>类型</th>
                    <th>说明</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>pay_order_no</td><td>是</td><td>string</td><td>支付订单号</td></tr>
            </tbody>
        </table>
        <div class="label" style="color:#3183f4;font-weight:600;margin:10px 0 2px 0;">返回示例</div>
        <pre>
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
        </pre>

        <h3 style="margin-top:38px;">3. 支付回调通知说明</h3>
        <p>支付成功后，系统会自动POST通知到 <b>notify_url</b>（如有填写）。<br>参数如下：</p>
        <pre>
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
        </pre>
        <p>
            建议在测试时使用 <a href="https://webhook.site/" target="_blank">webhook.site</a> 获取一个临时URL作为notify_url，可直观观察回调POST内容。
        </p>

        <h3 style="margin-top:38px;">4. 代码调用示例</h3>
        <div class="label" style="color:#3183f4;font-weight:600;margin:10px 0 2px 0;">PHP下单示例</div>
        <pre>
$url = "https://allinpay.zhuli.pro/commonpay/api/pay.php";
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
        </pre>
        <div class="label" style="color:#3183f4;font-weight:600;margin:10px 0 2px 0;">PHP查询订单示例</div>
        <pre>
$url = "https://allinpay.zhuli.pro/commonpay/api/order.php?pay_order_no=BIZ202506090012371355";
$resp = file_get_contents($url);
echo $resp;
        </pre>
        <div class="label" style="color:#3183f4;font-weight:600;margin:10px 0 2px 0;">cURL命令行示例</div>
        <pre>
curl -X POST 'https://allinpay.zhuli.pro/commonpay/api/pay.php' \
  -H 'Content-Type: application/json' \
  -d '{"channel":"BRLWJ6S0","pay_order_no":"BIZ202506090012371355","amount":"0.01","subject":"测试商品-091937"}'
curl 'https://allinpay.zhuli.pro/commonpay/api/order.php?pay_order_no=BIZ202506090012371355'
        </pre>
<p>
            完整代码参考 <a href="https://webhook.site/" target="_blank">Github.com</a>。
        </p>
        <h3 style="margin-top:38px;">5. 常见问题</h3>
        <ul>
            <li><b>通道标识如何获取？</b> 后台“通道管理”新建后自动分配，复制即可。</li>
            <li><b>订单号重复会怎样？</b> 一定要全局唯一，否则会报错或覆盖。</li>
            <li><b>notify_url 可为空，不填则不会推送支付结果到业务系统。</b></li>
            <li><b>notify_url、return_url 必须公网可访问，否则无法收到通知或页面不会跳转。</b></li>
            <li><b>如何测试回调有效性？</b> 推荐用 webhook.site 见回调内容。</li>
        </ul>

        <p style="color:#888;margin-top:30px;">如需更多帮助，请联系系统管理员。</p>
    </div>
</div>
<?php require_once '../footer.php'; ?>