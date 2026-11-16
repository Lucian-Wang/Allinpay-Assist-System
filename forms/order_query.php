<?php
require_once '../db.php';
require_once '../allinpay/allinpay.php';

function check_allinpay_order($order_no, $db) {
    $stmt = $db->prepare("SELECT o.*, f.appid, f.cusid, f.priKey FROM form_orders o LEFT JOIN forms f ON o.form_id=f.id WHERE o.order_no=?");
    $stmt->bind_param('s', $order_no);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$order) return false;

    $params = [
        "appid"     => $order['appid'],
        "cusid"     => $order['cusid'],
        "reqsn"     => $order_no,
        "signtype"  => "RSA",
        "version"   => "11",
        "randomstr" => bin2hex(random_bytes(8))
    ];

    $priKey = format_private_key($order['priKey']);
    $privateKey = openssl_get_privatekey($priKey);
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
    $params['sign'] = base64_encode($signature);

    $ch = curl_init('https://vsp.allinpay.com/apiweb/unitorder/query');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response, true);
    if ($data && isset($data['trxstatus']) && $data['trxstatus'] === '0000') {
        $stmt = $db->prepare("UPDATE form_orders SET status='paid', updated_at=NOW() WHERE order_no=?");
        $stmt->bind_param('s', $order_no);
        $stmt->execute();
        $stmt->close();
        return true;
    }
    return false;
}

$order_no = $_GET['order_no'] ?? '';
if (!$order_no) {
    echo json_encode(['status'=>'error']);
    exit;
}

$db = get_db();
$stmt = $db->prepare("SELECT status FROM form_orders WHERE order_no=?");
if (!$stmt) die(json_encode(['status'=>'error']));
$stmt->bind_param('s', $order_no);
$stmt->execute();
$stmt->bind_result($status);
$stmt->fetch();
$stmt->close();

if ($status === 'paid') {
    echo json_encode(['status'=>'paid']);
    exit;
}

if (check_allinpay_order($order_no, $db)) {
    echo json_encode(['status'=>'paid']);
} else {
    echo json_encode(['status'=>'pending']);
}