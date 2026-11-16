<?php
/**
 * 通联支付回调验签示例（业务实际应查订单获得商户参数）
 */
require_once __DIR__ . '/AllInPayClient.php';

// 1. 获取订单号，查到用哪个商户参数
$pay_order_no = $_POST['out_trade_no'] ?? $_POST['orderNo'] ?? '';
if (!$pay_order_no) { echo 'fail'; exit; }
// 2. 根据订单号查数据库，获取商户参数
require_once __DIR__ . '/../db.php';
$db = get_db();
$stmt = $db->prepare("SELECT * FROM pay_orders WHERE pay_order_no=? LIMIT 1");
$stmt->bind_param('s', $pay_order_no);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$order) { echo 'fail'; exit; }
// 3. 获取商户参数（应从/merchants/查）
$merchant = [
    'appid' => $order['appid'],
    'cusid' => $order['cusid'],
    'priKey' => $order['priKey'], // 数据库存PEM
];
$client = new AllInPayClient($merchant);
if (!$client->verifyNotify($_POST)) { echo 'fail'; exit; }

// 4. 业务逻辑处理
// ...（更新订单、发业务回调、等）...
echo 'success';
exit;