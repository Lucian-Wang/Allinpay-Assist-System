<?php
header('Content-Type: application/json');
require_once '../../db.php';

$pay_order_no = $_GET['pay_order_no'] ?? '';
if (!$pay_order_no) {
    echo json_encode(['code'=>1, 'msg'=>'缺少 pay_order_no']);
    exit;
}

$db = get_db();
$sql = "SELECT o.*, c.channel, c.name AS channel_name, m.name AS merchant_name 
        FROM commonpay_orders o 
        LEFT JOIN commonpay_channels c ON o.channel_id=c.id 
        LEFT JOIN merchants m ON o.merchant_id=m.id 
        WHERE o.pay_order_no=?";
$stmt = $db->prepare($sql);
$stmt->bind_param('s', $pay_order_no);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    echo json_encode(['code'=>3, 'msg'=>'订单不存在']);
    exit;
}

echo json_encode([
    'code'=>0,
    'msg'=>'ok',
    'data'=>[
        'pay_order_no'      => $order['pay_order_no'],
        'status'            => $order['status'],
        'amount'            => $order['amount'],
        'subject'           => $order['subject'],
        'paid_at'           => $order['paid_at'],
        'allinpay_trxid'    => $order['allinpay_trxid']
    ]
]);
exit;