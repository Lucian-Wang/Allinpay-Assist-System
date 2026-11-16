<?php
/**
 * 通用支付API测试页面（独立于接口主站，适合第三方站外对接测试）
 * 1. 下单 -> 2. 跳转支付页 -> 3. 模拟回调（notify.php） -> 4. 查询订单
 */
$api_base = 'https://allinpay.zhuli.pro/commonpay/api/';
$pay_entry = 'https://allinpay.zhuli.pro/commonpay/pay.php';
$notify_entry = 'https://allinpay.zhuli.pro/commonpay/notify.php';

// ======= 配置区 =======
$channel = 'BRLWJ6S0'; // TODO: 填写你实际后台创建的通道标识
// =====================

$result = [];
$notify_result = '';
$order_query_result = [];
$err = '';

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['step']) && $_POST['step']==='pay') {
    // 1. 下单
    $pay_order_no = 'T'.date('YmdHis').rand(1000,9999);
    $subject = '测试商品-'.date('His');
    $params = [
        'channel'      => trim($_POST['channel']),
        'pay_order_no' => $pay_order_no,
        'amount'       => number_format(floatval($_POST['amount']?:'0.01'), 2, '.', ''),
        'subject'      => $subject,
        'notify_url'   => 'http://webhook.wsx.tax/08f7f57bb10f4991f3724df2b611cb71', // 建议测试用webhook.site可见推送内容
        'return_url'   => 'https://www.baidu.com/',
        'user_id'      => 'testuser',
        'biz_sys'      => 'apitest',
        'biz_order_no' => 'BIZ'.date('YmdHis'),
        'extra'        => 'demo'
    ];
    $res = http_post_json($api_base.'pay.php', $params);
    $result = $res;
    if (isset($res['data']['pay_url'])) {
        $pay_url = $res['data']['pay_url'];
    } else {
        $err = $res['msg'] ?? '下单失败';
    }
}
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['step']) && $_POST['step']==='notify') {
    // 2. 模拟通联回调
    $pay_order_no = $_POST['pay_order_no'];
    $notify_data = [
        'reqsn'      => $pay_order_no,
        'trxstatus'  => '0000',
        'trxid'      => 'TRX'.date('YmdHis').rand(100,999),
        'trxamt'     => intval(floatval($_POST['amount'])*100), // 单位分
        'fintime'    => date('Y-m-d H:i:s')
    ];
    $notify_result = http_post_urlencode($notify_entry, $notify_data);
}
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['step']) && $_POST['step']==='query') {
    // 3. 查询订单
    $pay_order_no = $_POST['pay_order_no'];
    $order_query_result = http_get($api_base . 'order.php?pay_order_no=' . urlencode($pay_order_no));
}

// ==== 工具函数 ====
function http_post_json($url, $data) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_TIMEOUT => 10,
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    return json_decode($resp, true) ?: ['raw'=>$resp];
}
function http_post_urlencode($url, $data) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_TIMEOUT => 10,
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    return $resp;
}
function http_get($url) {
    $resp = file_get_contents($url);
    return json_decode($resp, true) ?: ['raw'=>$resp];
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>通用支付API外部测试页</title>
    <style>
        body { font-family: 'Segoe UI', 'PingFang SC', Arial, sans-serif; background: #f7fafd; margin: 0; padding: 0; }
        .box { max-width: 700px; margin: 38px auto; background: #fff; border-radius: 16px; box-shadow: 0 4px 20px #3183f433; padding: 32px; }
        .title { font-size: 22px; font-weight: bold; color: #3183f4; margin-bottom: 22px; }
        .field { margin-bottom: 18px; }
        .label { display: block; margin-bottom: 6px; color: #2d8cf0; font-weight: 500;}
        input[type=text], input[type=number] { width: 100%; padding: 8px; border: 1px solid #e3e9f7; border-radius: 7px; font-size: 15px; }
        .btn { padding: 9px 28px; background: #3183f4; color: #fff; border: none; border-radius: 7px; font-size: 15px; cursor: pointer; }
        .btn:hover { background: #246acc; }
        pre { background: #f7fafd; border: 1px solid #e3e9f7; border-radius: 7px; padding: 9px 12px; font-size: 14px; }
        .err { color: #f44336; }
        .step-title { color: #3183f4; font-weight: bold; margin: 24px 0 7px 0;}
        .payurl { font-size: 15px; color: #3183f4; word-break: break-all; }
    </style>
</head>
<body>
<div class="box">
    <div class="title">通用支付API外部测试页</div>
    <form method="post">
        <div class="field">
            <div class="label">通道标识 <span style="color:#888">(后台创建后复制填入)</span></div>
            <input type="text" name="channel" value="<?=htmlspecialchars($channel)?>" required>
        </div>
        <div class="field">
            <div class="label">支付金额(元)</div>
            <input type="number" name="amount" step="0.01" min="0.01" value="0.05" required>
        </div>
        <button class="btn" type="submit" name="step" value="pay">下单&获取支付URL</button>
    </form>
    <?php if ($err): ?>
        <div class="err"><?=htmlspecialchars($err)?></div>
    <?php endif; ?>
    <?php if (!empty($result)): ?>
        <div class="step-title">1. 下单接口返回</div>
        <pre><?=htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))?></pre>
        <?php if (isset($result['data']['pay_url'])): ?>
            <div class="step-title">2. 支付入口URL（可直接打开或扫码）</div>
            <div class="payurl"><?=htmlspecialchars($result['data']['pay_url'])?></div>
            <form method="post" style="margin:18px 0 0 0;">
                <input type="hidden" name="pay_order_no" value="<?=htmlspecialchars($result['data']['pay_order_no'])?>">
                <input type="hidden" name="amount" value="<?=htmlspecialchars($_POST['amount'])?>">
                <button class="btn" type="submit" name="step" value="notify">模拟通联回调（支付成功）</button>
                <button class="btn" type="submit" name="step" value="query" style="background:#43b244;margin-left:20px;">查询订单状态</button>
            </form>
        <?php endif;?>
    <?php endif;?>
    <?php if ($notify_result): ?>
        <div class="step-title">3. 通联回调接口返回</div>
        <pre><?=htmlspecialchars($notify_result)?></pre>
    <?php endif;?>
    <?php if ($order_query_result): ?>
        <div class="step-title">4. 订单查询接口返回</div>
        <pre><?=htmlspecialchars(json_encode($order_query_result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))?></pre>
    <?php endif;?>
    <div style="color:#999;font-size:13px;margin-top:32px;">
        * 此测试页为站外API调试用，不存储任何数据。notify_url建议用 <a href="https://webhook.site/" target="_blank">webhook.site</a> 测试回调推送效果。
    </div>
</div>
</body>
</html>