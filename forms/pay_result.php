<?php
require_once '../db.php';

$order_no = $_GET['order_no'] ?? '';
if (!$order_no) {
    exit('缺少订单号参数');
}

// 查询订单
$db = get_db();
$sql = "SELECT o.*, f.name AS form_name, f.code, m.name AS merchant_name 
        FROM form_orders o 
        LEFT JOIN forms f ON o.form_id=f.id 
        LEFT JOIN merchants m ON o.merchant_id=m.id 
        WHERE o.order_no=?";
$stmt = $db->prepare($sql);
$stmt->bind_param('s', $order_no);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    exit('订单不存在');
}

// 解析自定义字段
$field_values = [];
if (!empty($order['field_values'])) {
    $field_values = @json_decode($order['field_values'], true);
    if (!is_array($field_values)) $field_values = [];
}

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
        .pay-container {width:100%;max-width:370px;margin:60px auto 0 auto;background:#fff;border-radius:20px;box-shadow:0 8px 36px rgba(44,104,255,0.10);padding:36px 24px 18px 24px;display:flex;flex-direction:column;align-items:center;box-sizing:border-box;}
        .status {font-size:28px;font-weight:700;margin-top:18px;margin-bottom:16px;}
        .succ {color:#2d8cf0;}
        .pending {color:#ff9800;}
        .fail {color:#f44336;}
        .info {font-size:17px;color:#555;margin:18px 0 8px 0;text-align:center;word-break:break-all;}
        .custom-fields {background:#f7fafd;padding:12px 12px 8px 12px;border-radius:10px;margin:8px 0 0 0;font-size:16px;}
        .custom-fields b {font-weight: 600;}
        .back-btn {display:inline-block;margin-top:23px;padding:10px 32px;background:linear-gradient(90deg,#1677ff 30%,#269fff 100%);color:#fff;border-radius:8px;text-decoration:none;font-size:16px;transition:background 0.18s;}
        .back-btn:hover {background:#135edb;}
        @media (max-width:500px) {.pay-container {margin:18px auto 0 auto;padding:15px 3vw 12px 3vw;}}
    </style>
</head>
<body>
    <div class="pay-container">
        <h2 style="color:#2d8cf0; font-size:20px; font-weight:600;"><?=
          $order['merchant_name'] ?? ''?><?= $order['form_name'] ? ' - ' . htmlspecialchars($order['form_name']) : ''?></h2>
        <div class="status <?=$status?>">
            <?=htmlspecialchars($status_show)?>
        </div>
        <div class="info">
                    <?php if (!empty($field_values)): ?>
            <?php foreach ($field_values as $label => $val): ?>
                <?=htmlspecialchars($label)?>：<?=htmlspecialchars($val)?><br>
            <?php endforeach; ?>
        <?php endif; ?>
            金额：<?=$amount?> 元<br>
            支付方式：<?=htmlspecialchars($pay_channel)?><br>
            订单号：<?=htmlspecialchars($order_no)?><br>
            <?php if ($trxid): ?>
            通联商户单号：<?=htmlspecialchars($trxid)?><br>
            <?php endif; ?>
            <?php if (!empty($order['remark'])): ?>
            备注：<?=htmlspecialchars($order['remark'])?><br>
            <?php endif; ?>
        </div>

        <a href="pay.php?<?= $order['code'] ? 'code='.urlencode($order['code']) : 'form_id='.urlencode($order['form_id']) ?>" class="back-btn">返回收银台</a>
    </div>
</body>
</html>