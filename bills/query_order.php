<?php
require_once '../db.php';
require_once '../allinpay/allinpay.php';

$order_no = $_GET['order_no'] ?? $_POST['order_no'] ?? '';
if (!$order_no) {
    echo json_encode(['code'=>1, 'msg'=>'缺少 order_no']);
    exit;
}
$db = get_db();

// 注意：priKey 字段名如果在 merchants 表中实际为 priKey，请同步修改为 priKey
$sql = "SELECT o.*, m.appid, m.cusid, m.priKey FROM bills_orders o LEFT JOIN merchants m ON o.merchant_id=m.id WHERE o.order_no=?";
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
// 已支付时同步到bills_items
if ($order['status'] === 'paid') {
    $now = $order['paid_at'] ?: date('Y-m-d H:i:s');
    $stmt3 = $db->prepare("UPDATE bills_items SET pay_status=1, paid_at=? WHERE id=?");
    if (!$stmt3) {
        die("SQL prepare failed: " . $db->error . "<br>SQL: UPDATE bills_items SET pay_status=1, paid_at=? WHERE id=" . intval($order['item_id']));
    }
    $stmt3->bind_param('si', $now, $order['item_id']);
    $stmt3->execute();
    $stmt3->close();

    echo json_encode([
        'code' => 0,
        'status' => 'paid',
        'paid_at' => $now,
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
    $result && isset($result['retcode']) && $result['retcode'] === 'SUCCESS'
    && isset($result['trxstatus']) && $result['trxstatus'] === '0000'
) {
    $now = date('Y-m-d H:i:s');
    $trxid = $result['trxid'] ?? '';
    $stmt2 = $db->prepare("UPDATE bills_orders SET status='paid', paid_at=?, updated_at=?, trxid=? WHERE order_no=?");
    if (!$stmt2) {
        die("SQL prepare failed: " . $db->error . "<br>SQL: UPDATE bills_orders SET status='paid', paid_at=?, updated_at=?, trxid=? WHERE order_no=" . htmlspecialchars($order_no));
    }
    $stmt2->bind_param('ssss', $now, $now, $trxid, $order_no);
    $stmt2->execute();
    $stmt2->close();

    // 同步到bills_items
    $stmt3 = $db->prepare("UPDATE bills_items SET pay_status=1, paid_at=? WHERE id=?");
    if (!$stmt3) {
        die("SQL prepare failed: " . $db->error . "<br>SQL: UPDATE bills_items SET pay_status=1, paid_at=? WHERE id=" . intval($order['item_id']));
    }
    $stmt3->bind_param('si', $now, $order['item_id']);
    $stmt3->execute();
    $stmt3->close();

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