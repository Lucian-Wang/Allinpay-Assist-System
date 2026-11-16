<?php
require_once '../db.php';
require_once '../allinpay/allinpay.php';

// 获取参数
$channel      = $_POST['channel'] ?? '';
$pay_order_no = $_POST['pay_order_no'] ?? '';
$amount       = $_POST['amount'] ?? '';
$subject      = $_POST['subject'] ?? '';
$user_id      = $_POST['user_id'] ?? '';
$biz_sys      = $_POST['biz_sys'] ?? '';
$biz_order_no = $_POST['biz_order_no'] ?? '';
$notify_url   = $_POST['notify_url'] ?? '';
$return_url   = $_POST['return_url'] ?? '';
$extra        = $_POST['extra'] ?? '';
$paytype      = $_POST['paytype'] ?? '';
$remark       = trim($_POST['remark'] ?? '');

// 校验参数（notify_url, return_url 可选）
if (
    !$channel || !$pay_order_no || !$amount || !is_numeric($amount) || $amount < 0.01 ||
    !$subject
) exit('参数错误');

// 查找通道和商户
$db = get_db();
$sql = "SELECT c.*, m.id AS merchant_id, m.user_id, m.name AS merchant_name, m.appid, m.cusid, m.priKey 
        FROM commonpay_channels c 
        LEFT JOIN merchants m ON c.merchant_id=m.id 
        WHERE c.channel=? AND c.status=1";
$stmt = $db->prepare($sql);
$stmt->bind_param('s', $channel);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$row) exit('支付通道不存在或已禁用');

// 检查订单号是否重复
$sql = "SELECT status FROM commonpay_orders WHERE pay_order_no=?";
$stmt = $db->prepare($sql);
$stmt->bind_param('s', $pay_order_no);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 如果订单已支付，直接跳转
if ($order && $order['status'] === 'paid') {
    header("Location: pay_result.php?pay_order_no=" . urlencode($pay_order_no));
    exit;
}

// 只写一次订单
if (!$order) {
    $insert_paytype = $paytype ?: 'allinpay';
    $sql = "INSERT INTO commonpay_orders 
        (channel_id, merchant_id, pay_order_no, amount, pay_channel, status, subject, notify_url, return_url, user_id, biz_sys, biz_order_no, extra, remark, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
    $stmt = $db->prepare($sql);
    $stmt->bind_param(
        'iisdssssssssss',
        $row['id'],
        $row['merchant_id'],
        $pay_order_no,
        $amount,
        $channel,
        $subject,
        $notify_url,
        $return_url,
        $user_id,
        $biz_sys,
        $biz_order_no,
        $extra,
        $remark
    );
    $stmt->execute();
    $stmt->close();
}

// 自动判定通道
function detect_paytype($paytype) {
    if ($paytype) return $paytype;
    $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    if (strpos($ua, 'micromessenger') !== false) {
        return 'W01'; // 微信
    } elseif (strpos($ua, 'alipayclient') !== false) {
        return 'A01'; // 支付宝
    } else {
        return 'W01'; // 默认微信
    }
}
$final_paytype = detect_paytype($paytype);

// 通联下单参数
$params = [
    "appid"      => $row['appid'],
    "body"       => $subject,
    "cusid"      => $row['cusid'],
    "paytype"    => $final_paytype,
    "randomstr"  => bin2hex(random_bytes(8)),
    "reqsn"      => $pay_order_no,
    "signtype"   => "RSA",
    "trxamt"     => intval(bcmul($amount, 100)), // 元转分
    "version"    => "11"
];

// 私钥格式化&加载
$priKeyRaw = $row['priKey'] ?? '';
$priKey = format_private_key($priKeyRaw);
$privateKey = openssl_get_privatekey($priKey);
if (!$privateKey) {
    file_put_contents('/tmp/debug_commonpay_key.pem', $priKey);
    die("私钥加载失败");
}

// 签名
ksort($params);
$signStr = '';
foreach ($params as $k => $v) {
    if ($v !== '' && $v !== null && $k != 'sign') {
        $signStr .= $k . '=' . $v . '&';
    }
}
$signStr = rtrim($signStr, '&');
$signature = '';
openssl_sign($signStr, $signature, $privateKey, OPENSSL_ALGO_SHA1);
unset($privateKey);
$params['sign'] = base64_encode($signature);

// 下单
$ch = curl_init('https://vsp.allinpay.com/apiweb/unitorder/pay');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded'
]);
$response = curl_exec($ch);
curl_close($ch);
$data = json_decode($response, true);

if (!$data || !isset($data['payinfo'])) {
    // 如果通联返回订单已支付，也直接跳到结果页
    if (isset($data['retmsg']) && strpos($data['retmsg'], '订单已经支付') !== false) {
        header("Location: pay_result.php?pay_order_no=" . urlencode($pay_order_no));
        exit;
    }
    echo "<h2>下单失败：</h2><pre>".htmlspecialchars($response)."</pre>";
    exit;
}
$payinfo = $data['payinfo'];

// 判断端
function get_pay_env() {
    $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    if (strpos($ua, 'micromessenger') !== false) {
        return 'wechat';
    } elseif (strpos($ua, 'alipayclient') !== false) {
        return 'alipay';
    } else {
        return 'pc';
    }
}
$pay_env = get_pay_env();

if ($pay_env === 'wechat' || $pay_env === 'alipay') {
    header("Location: {$payinfo}");
    exit;
} else {
    // PC端二维码页面UI同 codes/pay_action.php
    $scan_tip = ($final_paytype === 'W01')
        ? '请使用<strong style="color:#1c8;">微信</strong>扫一扫支付'
        : '请使用<strong style="color:#1677ff;">支付宝</strong>扫一扫支付';
    // 拼接参数用于返回
    $back_url = "pay.php?channel=" . urlencode($channel)
        . "&pay_order_no=" . urlencode($pay_order_no)
        . "&amount=" . urlencode($amount)
        . "&subject=" . urlencode($subject)
        . "&user_id=" . urlencode($user_id)
        . "&biz_sys=" . urlencode($biz_sys)
        . "&biz_order_no=" . urlencode($biz_order_no)
        . "&notify_url=" . urlencode($notify_url)
        . "&return_url=" . urlencode($return_url)
        . "&extra=" . urlencode($extra);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>扫码支付</title>
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
        <style>
            body {
                background: linear-gradient(135deg, #eaf2fe 0%, #f5f7fa 100%);
                font-family: 'Segoe UI', 'PingFang SC', 'Arial', sans-serif;
                margin: 0;
                padding: 0;
                min-height: 100vh;
            }
            .pay-container {
                width: 100%;
                max-width: 370px;
                margin: 60px auto 0 auto;
                background: #fff;
                border-radius: 20px;
                box-shadow: 0 8px 36px rgba(44, 104, 255, 0.10);
                padding: 36px 24px 18px 24px;
                display: flex;
                flex-direction: column;
                align-items: center;
                box-sizing: border-box;
            }
            .scan-tip {
                font-size: 18px;
                margin-bottom: 18px;
                color: #2d8cf0;
                font-weight: 500;
                letter-spacing: 1px;
                text-align: center;
            }
            .qr {
                margin: 0 auto 16px auto;
                background: #f7faff;
                padding: 18px;
                border-radius: 14px;
                box-shadow: 0 2px 10px rgba(44,104,255,0.07);
            }
            .info {
                font-size: 15px;
                color: #555;
                margin-top: 16px;
                word-break: break-all;
                text-align: center;
            }
            .back-btn {
                display: inline-block;
                margin-top: 28px;
                padding: 10px 32px;
                background: linear-gradient(90deg, #1677ff 30%, #269fff 100%);
                color: #fff;
                border-radius: 8px;
                text-decoration: none;
                font-size: 16px;
                transition: background 0.18s;
            }
            .back-btn:hover { background: #135edb; }
            @media (max-width: 500px) {
                .pay-container {
                    margin: 18px auto 0 auto;
                    padding: 15px 3vw 12px 3vw;
                }
            }
        </style>
        <script>
        setInterval(function(){
            fetch("/commonpay/query_order.php?pay_order_no=<?=urlencode($pay_order_no)?>")
            .then(r=>r.json()).then(d=>{
                if(d.status === 'paid'){
                    window.location.href = "pay_result.php?pay_order_no=<?=urlencode($pay_order_no)?>";
                }
            });
        }, 2000);
        </script>
    </head>
    <body>
        <div class="pay-container">
            <div class="scan-tip"><?=$scan_tip?></div>
            <div class="qr">
                <img src="https://lib.zhuli.pro/qrcode.php?data=<?=urlencode($payinfo)?>" width="240" height="240" alt="二维码">
            </div>
            <div class="info">
                金额：<?=number_format($amount,2)?> 元<br>
                订单号：<?=htmlspecialchars($pay_order_no)?><br>
                <?php if ($remark): ?>
                备注：<?=htmlspecialchars($remark)?><br>
                <?php endif; ?>
                <a href="<?=$back_url?>" class="back-btn">返回/更换支付方式</a>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>