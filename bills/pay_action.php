<?php
require_once '../db.php';
require_once '../allinpay/allinpay.php';

$code = $_POST['code'] ?? '';
$item_id = intval($_POST['item_id'] ?? 0);
$paytype = $_POST['paytype'] ?? '';

if (!$code || !$item_id) exit('参数错误');

$db = get_db();
$sql = "SELECT i.*, p.id AS project_id, p.code AS project_code, p.merchant_id, p.user_id AS project_user_id, p.name AS project_name, m.name AS merchant_name, m.appid, m.cusid, m.priKey
        FROM bills_items i
        LEFT JOIN bills_projects p ON i.project_id=p.id
        LEFT JOIN merchants m ON p.merchant_id = m.id
        WHERE i.id=? AND p.code=? AND i.status=1 AND p.status=1 AND i.pay_status=0";
$stmt = $db->prepare($sql);
$stmt->bind_param('is', $item_id, $code);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) exit('账单不存在或参数不匹配');

// 检查是否已有订单
$stmt = $db->prepare("SELECT status, order_no FROM bills_orders WHERE item_id=? AND status='pending'");
$stmt->bind_param('i', $item_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($order && $order['status'] === 'paid') {
    header("Location: pay_result.php?order_no=" . urlencode($order['order_no']));
    exit;
}

// 只有第一次才写订单
if (!$order) {
    $order_no = 'B' . date('YmdHis') . rand(1000,9999);
    $sql = "INSERT INTO bills_orders (project_id, item_id, user_id, merchant_id, order_no, amount, pay_channel, status, customer_tag, customer_name, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, NOW(), NOW())";
    $stmt = $db->prepare($sql);
    $stmt->bind_param(
        'iiiisdsss',
        $item['project_id'],
        $item['id'],
        $item['project_user_id'],
        $item['merchant_id'],
        $order_no,
        $item['amount'],
        $paytype,
        $item['customer_tag'],
        $item['customer_name']
    );
    $stmt->execute();
    $stmt->close();
} else {
    $order_no = $order['order_no'];
}

// 自动判定通道
function detect_paytype($paytype) {
    if ($paytype) return $paytype;
    $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    if (strpos($ua, 'micromessenger') !== false) return 'W01';
    if (strpos($ua, 'alipayclient') !== false) return 'A01';
    return 'W01';
}
$final_paytype = detect_paytype($paytype);

// 通联下单参数
$params = [
    "appid"      => $item['appid'],
    "body"       => $item['project_name'] . "账单收款",
    "cusid"      => $item['cusid'],
    "paytype"    => $final_paytype,
    "randomstr"  => bin2hex(random_bytes(8)),
    "reqsn"      => $order_no,
    "signtype"   => "RSA",
    "trxamt"     => intval(bcmul($item['amount'], 100)),
    "version"    => "11"
];

// 使用 format_private_key 适配各种私钥格式
$priKey = format_private_key($item['priKey'] ?? '');
$privateKey = openssl_get_privatekey($priKey);
if (!$privateKey) die("私钥加载失败");

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

// 保存 payinfo
$stmt = $db->prepare("UPDATE bills_orders SET payinfo=? WHERE order_no=?");
$stmt->bind_param('ss', $payinfo, $order_no);
$stmt->execute();
$stmt->close();

// 跳转二维码页
header("Location: pay_qr.php?order_no=".urlencode($order_no));
exit;