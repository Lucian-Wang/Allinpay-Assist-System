<?php
require_once __DIR__ . '/AllInPayClient.php';
require_once __DIR__ . '/pay_env.php';

// 商户参数（建议从数据库读取，这里仅演示）
$merchant = [
    'appid'      => '00356343',
    'cusid'      => '5644610893141KE',
    'priKey'     => file_get_contents('/www/wwwroot/allinpay.zhuli.pro/certs/merchant_rsa_private.pem'),
    // 'pubKeyPath'=> '/www/wwwroot/allinpay.zhuli.pro/certs/allinpay_public.pem', // 可省略
];

$client = new AllInPayClient($merchant);

$amount = 1; // 单位：分
$order_no = 'DEMO' . date('YmdHis') . rand(1000,9999);
$subject = '支付demo';
$return_url = 'https://yourdomain.com/success.php';

// 判断环境，选择支付方式
$pay_env = get_pay_env();
$opts = ['return_url'=>$return_url, 'client_ip'=>$_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'];
if ($pay_env === 'wechat') {
    $paytype = 'wxh5';
} elseif ($pay_env === 'alipay') {
    $paytype = 'alih5';
} else {
    $paytype = 'W01'; // 默认扫码
}
$res = $client->createOrder($paytype, $amount, $order_no, $subject, $opts);

if ($res['code'] === 0) {
    if ($pay_env === 'wechat' || $pay_env === 'alipay') {
        // H5环境，直接跳转
        header("Location: {$res['payinfo']}");
        exit;
    } else {
        // 普通浏览器，展示二维码
        $qrcode_url = $res['payinfo'];
        echo "<h3>请用微信/支付宝App扫码支付</h3>";
        echo "<img src='https://api.qrserver.com/v1/create-qr-code/?data=" . urlencode($qrcode_url) . "&size=220x220'>";
        echo "<p>订单号: $order_no</p>";
        exit;
    }
} else {
    echo "下单失败: {$res['msg']}";
}