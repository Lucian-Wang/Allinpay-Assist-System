<?php
require_once '../db.php';
require_once '../allinpay/allinpay.php';

$order_no = $_GET['order_no'] ?? $_POST['order_no'] ?? '';
if (!$order_no) {
    echo json_encode(['code'=>1, 'msg'=>'缺少 order_no']);
    exit;
}

$db = get_db();
// 将 priKey 改为 priKey，确保与 merchants 表字段一致
$sql = "SELECT o.*, m.appid, m.cusid, m.priKey FROM form_orders o LEFT JOIN merchants m ON o.merchant_id=m.id WHERE o.order_no=?";
$stmt = $db->prepare($sql);
if (!$stmt) {
    die("SQL prepare failed: " . $db->error . "<br>SQL: " . htmlspecialchars($sql));
}
$stmt->bind_param('s', $order_no);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    echo json_encode(['code'=>2, 'msg'=>'订单不存在']);
    exit;
}

if ($order['status'] === 'paid') {
    echo json_encode([
        'code' => 0,
        'status' => 'paid',
        'paid_at' => $order['paid_at'] ?? '',
        'trxid' => $order['trxid'] ?? '',
        'msg' => 'ok'
    ]);
    exit;
}

$appid = $order['appid'];
$cusid = $order['cusid'];
$priKey = $order['priKey'];

if (!$appid || !$cusid || !$priKey) {
    echo json_encode(['code'=>3,'msg'=>'商户参数缺失']);
    exit;
}

$result = allinpay_query_order($order_no, $appid, $cusid, $priKey);

if (
    $result
    && isset($result['retcode']) && $result['retcode'] === 'SUCCESS'
    && isset($result['trxstatus']) && $result['trxstatus'] === '0000'
) {
    $now = date('Y-m-d H:i:s');
    $trxid = $result['trxid'] ?? '';
    $stmt2 = $db->prepare("UPDATE form_orders SET status='paid', paid_at=?, updated_at=?, trxid=? WHERE order_no=?");
    if (!$stmt2) {
        die("SQL prepare failed: " . $db->error . "<br>SQL: UPDATE form_orders SET status='paid', paid_at=?, updated_at=?, trxid=? WHERE order_no=" . htmlspecialchars($order_no));
    }
    $stmt2->bind_param('ssss', $now, $now, $trxid, $order_no);
    $stmt2->execute();
    $stmt2->close();

    echo json_encode([
        'code' => 0,
        'status' => 'paid',
        'paid_at' => $now,
        'trxid' => $trxid,
        'msg' => 'ok'
    ]);
    exit;
}

echo json_encode(['code'=>0, 'status'=>'unpaid', 'msg'=>'未支付']);
exit;
?>