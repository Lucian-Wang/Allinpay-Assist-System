<?php
$page_title = '通用支付接口使用说明';
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
    background: #f7faff;
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
        <div class="card-title">通用支付接口使用说明</div>
        <div class="card-actions">
            <a href="/commonpay/index.php">通道管理</a>
            <a href="/users/index.php">用户中心</a>
        </div>
    </div>
    <div style="padding:24px 16px;">

        <h2 style="color:#3183f4;">接口入口示例</h2>
        <div style="background:#f7faff;border:1px solid #e3e9f7;padding:10px 16px;word-break:break-all;margin-bottom:18px;">
            https://allinpay.zhuli.pro/commonpay/pay.php?<br>
            channel=<b>BRLWJ6S0</b>&amp;pay_order_no=<b>BIZ202506090012371355</b>&amp;amount=0.01&amp;subject=%E6%B5%8B%E8%AF%95%E5%95%86%E5%93%81-091937&amp;user_id=123456&amp;biz_sys=testsaas&amp;biz_order_no=BIZ202506090919371354&amp;notify_url=https%3A%2F%2Fpay.wsx.tax%2Fapi%2Fallinpay_notify.php&amp;return_url=https%3A%2F%2Fpay.wsx.tax%2Fapi%2F&amp;extra=
        </div>

        <h3>参数说明</h3>
        <table class="table" style="width:100%;background:#fff;">
            <thead>
                <tr>
                    <th>参数名</th>
                    <th>必填</th>
                    <th>说明</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>channel</td><td>是</td><td>通道标识，后台“通道管理”自动生成</td></tr>
                <tr><td>pay_order_no</td><td>是</td><td>平台支付订单号，需全局唯一</td></tr>
                <tr><td>amount</td><td>是</td><td>金额(元)，如0.01</td></tr>
                <tr><td>subject</td><td>是</td><td>商品名称，需urlencode</td></tr>
                <tr><td>user_id</td><td>否</td><td>业务用户ID，可选</td></tr>
                <tr><td>biz_sys</td><td>否</td><td>业务系统标识，可选</td></tr>
                <tr><td>biz_order_no</td><td>否</td><td>业务订单号，可选</td></tr>
                <tr><td>notify_url</td><td>否</td><td>支付后回调通知地址，需urlencode（可选，不填则不回调）</td></tr>
                <tr><td>return_url</td><td>否</td><td>支付完成后跳转地址，需urlencode</td></tr>
                <tr><td>extra</td><td>否</td><td>业务扩展参数</td></tr>
            </tbody>
        </table>

        <h3 style="margin-top:28px;">拼装支付链接示例（PHP）</h3>
        <pre>
$params = [
    'channel' => 'BRLWJ6S0',
    'pay_order_no' => 'BIZ202506090012371355',
    'amount' => '0.01',
    'subject' => urlencode('测试商品-091937'),
    'user_id' => '123456',
    'biz_sys' => 'testsaas',
    'biz_order_no' => 'BIZ202506090919371354',
    'notify_url' => urlencode('https://xxx.xx.com/api/allinpay_notify.php'),
    'return_url' => urlencode('https://xxx.xx.com/api/'),
    'extra' => ''
];
$query = http_build_query($params);
$url = 'https://allinpay.zhuli.pro/commonpay/pay.php?' . $query;
        </pre>

        <h3 style="margin-top:28px;">通联回调地址说明</h3>
        <p>
            <b>通联支付平台需配置的回调地址为：</b><br>
            <code>https://allinpay.zhuli.pro/commonpay/notify.php</code><br>
            <br>
            请在通联商户后台或对接参数中将上述地址填写为异步通知（支付结果通知）URL。<br>
            当用户支付完成后，通联服务器会向该地址推送支付结果，系统会自动处理订单并主动通知业务系统（notify_url）。<br>
            <b>注意：</b>此地址必须可被通联服务器公网访问。
        </p>

        <h3 style="margin-top:28px;">回调调试方法</h3>
        <ol>
            <li>
                <b>测试通联回调：</b>
                <ul>
                    <li>可通过真实支付一笔订单，查看后台订单状态和业务系统是否收到回调。</li>
                    <li>也可使用 <code>curl</code> 或 <b>Postman</b> 向 <code>/commonpay/notify.php</code> 手动POST模拟通联回调。例如：
                        <pre>curl -X POST 'https://allinpay.zhuli.pro/commonpay/notify.php' -d "reqsn=订单号&trxstatus=0000&trxid=交易号&trxamt=100&fintime=2025-06-09 11:00:00"</pre>
                    </li>
                </ul>
            </li>
            <li>
                <b>测试业务回调（notify_url）：</b>
                <ul>
                    <li>推荐使用 <a href="https://webhook.site/" target="_blank">webhook.site</a> 获取一个临时URL，作为notify_url参数。</li>
                    <li>完成支付或主动查单后，回到webhook.site页面，可直接看到系统POST的回调数据。</li>
                    <li>便于验证字段是否齐全、格式是否正确。</li>
                </ul>
            </li>
            <li>
                <b>排查建议：</b>
                <ul>
                    <li>如未收到回调，请检查回调地址是否正确、是否可公网访问。</li>
                    <li>可在系统后台订单详情中查看notify_result字段，排查业务系统回调响应。</li>
                </ul>
            </li>
        </ol>

        <h3 style="margin-top:28px;">回调通知</h3>
        <p>
            支付成功后，系统会自动POST通知到notify_url（如有填写），请确保notify_url为公网可访问。<br>
            通知参数请参考后台配置或联系技术支持。
        </p>

        <h3 style="margin-top:28px;">常见问题</h3>
        <ul>
            <li><b>通道标识如何获取？</b> 后台“通道管理”新建后自动分配，复制即可。</li>
            <li><b>订单号重复会怎样？</b> 一定要全局唯一，否则会报错或覆盖。</li>
            <li><b>notify_url 可为空，不填则不会推送支付结果到业务系统。</b></li>
            <li><b>notify_url、return_url 必须公网可访问，否则无法收到通知或页面不会跳转。</b></li>
            <li><b>如何测试回调有效性？</b> 详见上文“回调调试方法”说明。</li>
        </ul>

        <p style="color:#888;margin-top:30px;">如需更多帮助，请联系系统管理员。</p>
    </div>
</div>
<?php require_once '../footer.php'; ?>