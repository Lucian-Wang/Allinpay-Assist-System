<?php
require_once '../db.php';
$order_no = $_GET['order_no'] ?? '';
if (!$order_no) exit('缺少订单号参数');

$db = get_db();
$sql = "SELECT o.*, i.customer_name, i.customer_tag, i.remark, i.amount, p.name AS project_name, p.code AS project_code, m.name AS merchant_name
        FROM bills_orders o 
        LEFT JOIN bills_items i ON o.item_id=i.id 
        LEFT JOIN bills_projects p ON o.project_id=p.id 
        LEFT JOIN merchants m ON o.merchant_id=m.id 
        WHERE o.order_no=?";
$stmt = $db->prepare($sql);
$stmt->bind_param('s', $order_no);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) exit('订单不存在');

$status_text = [
    'pending' => '待支付',
    'paid' => '支付成功',
    'fail' => '支付失败',
    'closed' => '已关闭',
];

$status = $order['status'] ?? 'pending';
$status_show = $status_text[$status] ?? $status;
$amount = number_format($order['amount'], 2);
$pay_channel = $order['pay_channel'] === 'W01' ? '微信' : ($order['pay_channel'] === 'A01' ? '支付宝' : $order['pay_channel']);
$trxid = $order['trxid'] ?? '';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>支付结果 - <?=$order['merchant_name'] ?? '商户'?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <style>
        body {background: linear-gradient(135deg, #eaf2fe 0%, #f5f7fa 100%);font-family: 'Segoe UI','PingFang SC','Arial',sans-serif;margin:0;padding:0;min-height:100vh;}
        .pay-container {width:100%;max-width:370px;margin:60px auto 0 auto;background:#fff;border-radius:20px;box-shadow:0 8px 36px rgba(44,104,255,0.10);padding:36px 24px 18px 24px;}
        .status {font-size:28px;font-weight:700;margin-top:18px;margin-bottom:16px;}
        .succ {color:#2d8cf0;}
        .pending {color:#ff9800;}
        .fail {color:#f44336;}
        .info {font-size:17px;color:#555;margin:18px 0 8px 0;text-align:center;word-break:break-all;}
        .back-btn {display:inline-block;margin-top:23px;padding:10px 32px;background:linear-gradient(90deg,#1677ff 30%,#269fff 100%);color:#fff;border-radius:8px;text-decoration:none;font-size:16px;}
        .back-btn:hover {background:#135edb;}
    </style>
</head>
<body>
    <div class="pay-container">
        <h2 style="color:#2d8cf0; font-size:20px; font-weight:600;"><?=$order['merchant_name'] ?? ''?><?= $order['project_name'] ? ' - ' . htmlspecialchars($order['project_name']) : ''?></h2>
        <div class="status <?=$status?>">
            <?=htmlspecialchars($status_show)?>
        </div>
        <div class="info">
            客户：<?=htmlspecialchars($order['customer_name'])?> (<?=htmlspecialchars($order['customer_tag'])?>)<br>
            金额：<?=$amount?> 元<br>
            账单：<?=htmlspecialchars($order['remark'])?><br>
            支付方式：<?=htmlspecialchars($pay_channel)?><br>
            订单号：<?=htmlspecialchars($order_no)?><br>
            <?php if ($trxid): ?>
            通联商户单号：<?=htmlspecialchars($trxid)?><br>
            <?php endif; ?>
        </div>
        <a href="pay.php?code=<?=urlencode($order['project_code'])?>&item_id=<?=urlencode($order['item_id'])?>" class="back-btn">返回收银台</a>
    </div>
</body>
</html>