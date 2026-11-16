<?php
require_once '../db.php';
require_once '../allinpay/allinpay.php';

// 获取参数
$form_id = $_POST['form_id'] ?? '';
$code    = $_POST['code'] ?? '';
$order_no = $_POST['order_no'] ?? '';
$amount  = $_POST['amount'] ?? '';
$paytype = $_POST['paytype'] ?? '';
$remark = trim($_POST['remark'] ?? '');

if ((!$form_id && !$code) || !$order_no || !$amount || !is_numeric($amount) || $amount < 0.01) exit('参数错误');

$db = get_db();
if ($code) {
    $sql = "SELECT f.*, m.id AS merchant_id, m.user_id, m.name AS merchant_name, m.appid, m.cusid, m.priKey 
        FROM forms f 
        LEFT JOIN merchants m ON f.merchant_id=m.id 
        WHERE f.code=? AND f.status=1";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('s', $code);
} else {
    $sql = "SELECT f.*, m.id AS merchant_id, m.user_id, m.name AS merchant_name, m.appid, m.cusid, m.priKey 
        FROM forms f 
        LEFT JOIN merchants m ON f.merchant_id=m.id 
        WHERE f.id=? AND f.status=1";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $form_id);
}
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$row) exit('表单不存在或已禁用');

// 查字段
$stmt = $db->prepare("SELECT * FROM form_fields WHERE form_id=? ORDER BY sort ASC,id ASC");
$stmt->bind_param('i', $row['id']);
$stmt->execute();
$fields = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 收集自定义字段
$field_values = [];
foreach ($fields as $f) {
    $k = 'field_' . $f['id'];
    $field_values[$f['label']] = trim($_POST[$k] ?? '');
    if ($f['required'] && $field_values[$f['label']] === '') {
        exit($f['label'].'为必填项');
    }
}
$field_values_json = json_encode($field_values, JSON_UNESCAPED_UNICODE);

// 检查订单号是否重复（防止刷新重复下单）
$sql = "SELECT status FROM form_orders WHERE order_no=?";
$stmt = $db->prepare($sql);
$stmt->bind_param('s', $order_no);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 如果订单已支付，直接跳转到结果页或提示
if ($order && $order['status'] === 'paid') {
    header("Location: pay_result.php?order_no=" . urlencode($order_no));
    exit;
}

// 只有第一次才写订单
if (!$order) {
    $insert_paytype = $paytype ?: 'allinpay';
    $sql = "INSERT INTO form_orders (form_id, user_id, merchant_id, order_no, amount, pay_channel, status, remark, field_values, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, NOW(), NOW())";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('iiisssss', $row['id'], $row['user_id'], $row['merchant_id'], $order_no, $amount, $insert_paytype, $remark, $field_values_json);
    $stmt->execute();
    $stmt->close();
}

// 自动判定通道
function detect_paytype($paytype) {
    if ($paytype) return $paytype;
    $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    if (strpos($ua, 'micromessenger') !== false) {
        return 'W01';
    } elseif (strpos($ua, 'alipayclient') !== false) {
        return 'A01';
    } else {
        return 'W01';
    }
}
$final_paytype = detect_paytype($paytype);

// 通联下单参数
$params = [
    "appid"      => $row['appid'],
    "body"       => $row['merchant_name'] . "表单收款",
    "cusid"      => $row['cusid'],
    "paytype"    => $final_paytype,
    "randomstr"  => bin2hex(random_bytes(8)),
    "reqsn"      => $order_no,
    "signtype"   => "RSA",
    "trxamt"     => intval(bcmul($amount, 100)),
    "version"    => "11"
];

// 使用 format_private_key 适配各种私钥格式
$priKey = format_private_key($row['priKey'] ?? '');
$privateKey = openssl_get_privatekey($priKey);
if (!$privateKey) die("私钥加载失败");

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
    if (isset($data['retmsg']) && strpos($data['retmsg'], '订单已经支付') !== false) {
        header("Location: pay_result.php?order_no=" . urlencode($order_no));
        exit;
    }
    echo "<h2>下单失败：</h2><pre>".htmlspecialchars($response)."</pre>";
    exit;
}
$payinfo = $data['payinfo'];

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
    $scan_tip = ($final_paytype === 'W01')
        ? '请使用<strong style="color:#1c8;">微信</strong>扫一扫支付'
        : '请使用<strong style="color:#1677ff;">支付宝</strong>扫一扫支付';
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>扫码支付</title>
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
        <style>
            body {background: linear-gradient(135deg, #eaf2fe 0%, #f5f7fa 100%);font-family: 'Segoe UI','PingFang SC','Arial',sans-serif;margin:0;padding:0;min-height:100vh;}
            .pay-container {width:100%;max-width:370px;margin:60px auto 0 auto;background:#fff;border-radius:20px;box-shadow:0 8px 36px rgba(44,104,255,0.10);padding:36px 24px 18px 24px;display:flex;flex-direction:column;align-items:center;box-sizing:border-box;}
            .scan-tip {font-size:18px;margin-bottom:18px;color:#2d8cf0;font-weight:500;letter-spacing:1px;text-align:center;}
            .qr {margin:0 auto 16px auto;background:#f7faff;padding:18px;border-radius:14px;box-shadow:0 2px 10px rgba(44,104,255,0.07);}
            .info {font-size:15px;color:#555;margin-top:16px;word-break:break-all;text-align:center;}
            .back-btn {display:inline-block;margin-top:28px;padding:10px 32px;background:linear-gradient(90deg,#1677ff 30%,#269fff 100%);color:#fff;border-radius:8px;text-decoration:none;font-size:16px;transition:background 0.18s;}
            .back-btn:hover {background:#135edb;}
            @media (max-width:500px) {.pay-container {margin:18px auto 0 auto;padding:15px 3vw 12px 3vw;}}
        </style>
        <script>
        setInterval(function(){
            fetch("query_order.php?order_no=<?=urlencode($order_no)?>")
            .then(r=>r.json()).then(d=>{
                if(d.status === 'paid'){
                    window.location.href = "pay_result.php?order_no=<?=urlencode($order_no)?>";
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
                订单号：<?=htmlspecialchars($order_no)?><br>
                <?php if ($remark): ?>
                备注：<?=htmlspecialchars($remark)?><br>
                <?php endif; ?>
                <a href="pay.php?<?= $code ? 'code='.urlencode($code) : 'form_id='.urlencode($form_id) ?>" class="back-btn">返回/更换支付方式</a>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>