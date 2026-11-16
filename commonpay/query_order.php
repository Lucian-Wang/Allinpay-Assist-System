<?php
require_once '../db.php';
require_once '../allinpay/allinpay.php';

$pay_order_no = $_GET['pay_order_no'] ?? $_POST['pay_order_no'] ?? '';
if (!$pay_order_no) {
    echo json_encode(['code'=>1, 'msg'=>'缺少 pay_order_no']);
    exit;
}

$db = get_db();

$sql = "SELECT o.*, m.appid, m.cusid, m.priKey FROM commonpay_orders o LEFT JOIN merchants m ON o.merchant_id=m.id WHERE o.pay_order_no=?";
$stmt = $db->prepare($sql);
if (!$stmt) {
    die("SQL prepare failed: " . $db->error . "<br>SQL: " . htmlspecialchars($sql));
}
$stmt->bind_param('s', $pay_order_no);
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
        'trxid' => $order['allinpay_trxid'] ?? '',
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

$result = allinpay_query_order($pay_order_no, $appid, $cusid, $priKey);

if (
    $result
    && isset($result['retcode']) && $result['retcode'] === 'SUCCESS'
    && isset($result['trxstatus']) && $result['trxstatus'] === '0000'
) {
    $now = date('Y-m-d H:i:s');
    $trxid = $result['trxid'] ?? '';
    $stmt2 = $db->prepare("UPDATE commonpay_orders SET status='paid', paid_at=?, updated_at=?, allinpay_trxid=? WHERE pay_order_no=?");
    if (!$stmt2) {
        die("SQL prepare failed: " . $db->error . "<br>SQL: UPDATE commonpay_orders SET status='paid', paid_at=?, updated_at=?, allinpay_trxid=? WHERE pay_order_no=?");
    }
    $stmt2->bind_param('ssss', $now, $now, $trxid, $pay_order_no);
    $stmt2->execute();
    $stmt2->close();

    // 主动通知业务系统
    if ($order['notify_url']) {
        $callback = [
            'pay_order_no' => $pay_order_no,
            'user_id'      => $order['user_id'],
            'amount'       => $order['amount'],
            'status'       => 'paid',
            'paid_at'      => $now,
            'biz_sys'      => $order['biz_sys'],
            'biz_order_no' => $order['biz_order_no'],
            'extra'        => $order['extra']
        ];
        $curl = curl_init($order['notify_url']);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($callback));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        $notify_result = curl_exec($curl);
        curl_close($curl);

        // 记录业务回调结果
        $stmt3 = $db->prepare("UPDATE commonpay_orders SET notify_result=? WHERE pay_order_no=?");
        $stmt3->bind_param('ss', $notify_result, $pay_order_no);
        $stmt3->execute();
        $stmt3->close();
    }

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