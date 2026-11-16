<?php
require_once '../db.php';

// 获取参数
$pay_order_no = $_GET['pay_order_no'] ?? '';
if (!$pay_order_no) {
    exit('缺少订单号参数');
}

// 查询订单
$db = get_db();
$sql = "SELECT o.*, c.name AS channel_name, c.channel, m.name AS merchant_name 
        FROM commonpay_orders o 
        LEFT JOIN commonpay_channels c ON o.channel_id=c.id 
        LEFT JOIN merchants m ON o.merchant_id=m.id 
        WHERE o.pay_order_no=?";
$stmt = $db->prepare($sql);
$stmt->bind_param('s', $pay_order_no);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) exit('订单不存在');

// 状态映射
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

// 自动跳转（10秒）到业务系统
$auto_jump = '';
if ($order['return_url']) {
    $auto_jump = '<script>setTimeout(function(){location.href="' . htmlspecialchars($order['return_url']) . '";},10000);</script>';
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>支付结果 - <?=htmlspecialchars($order['merchant_name'] ?? '通用支付接口')?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <style>
        body {background: linear-gradient(135deg, #eaf2fe 0%, #f5f7fa 100%);font-family: 'Segoe UI', 'PingFang SC', 'Arial', sans-serif;margin: 0;padding: 0;min-height: 100vh;}
        .pay-container {width:100%;max-width:370px;margin:60px auto 0;background:#fff;border-radius:20px;box-shadow:0 8px 36px rgba(44,104,255,0.10);padding:36px 24px 18px;display:flex;flex-direction:column;align-items:center;box-sizing:border-box;}
        .status {font-size:28px;font-weight:700;margin-top:18px;margin-bottom:16px;}
        .succ { color: #2d8cf0; }
        .pending { color: #ff9800; }
        .fail { color: #f44336; }
        .info {font-size:17px;color:#555;margin:18px 0 8px 0;text-align:center;word-break:break-all;}
        .back-btn {display:inline-block;margin-top:23px;padding:10px 32px;background:linear-gradient(90deg,#1677ff 30%,#269fff 100%);color:#fff;border-radius:8px;text-decoration:none;font-size:16px;transition:background 0.18s;}
        .back-btn:hover { background: #135edb; }
        .tip { font-size:13px;color:#bbb;margin-top:16px; }
    </style>
    <?=$auto_jump?>
</head>
<body>
    <div class="pay-container">
        <h2 style="color:#2d8cf0; font-size:20px; font-weight:600;"><?= htmlspecialchars($order['merchant_name'] ?? '') ?><?= $order['channel_name'] ? ' - ' . htmlspecialchars($order['channel_name']) : ''?></h2>
        <div class="status <?=$status?>">
            <?=htmlspecialchars($status_show)?>
        </div>
        <div class="info">
            金额：<?=$amount?> 元<br>
            支付方式：<?=htmlspecialchars($pay_channel)?><br>
            订单号：<?=htmlspecialchars($pay_order_no)?><br>
            <?php if ($order['subject']): ?>
            商品名：<?=htmlspecialchars($order['subject'])?><br>
            <?php endif; ?>
            <?php if ($order['user_id']): ?>
            用户ID：<?=htmlspecialchars($order['user_id'])?><br>
            <?php endif; ?>
            <?php if ($order['biz_sys']): ?>
            业务系统：<?=htmlspecialchars($order['biz_sys'])?><br>
            <?php endif; ?>
            <?php if ($order['biz_order_no']): ?>
            业务订单号：<?=htmlspecialchars($order['biz_order_no'])?><br>
            <?php endif; ?>
            <?php if ($order['extra']): ?>
            扩展参数：<?=htmlspecialchars($order['extra'])?><br>
            <?php endif; ?>
            <?php if ($order['allinpay_trxid']): ?>
            通联交易号：<?=htmlspecialchars($order['allinpay_trxid'])?><br>
            <?php endif; ?>
        </div>
        <?php if ($order['return_url']): ?>
            <div class="tip">本页将在10秒后自动返回业务系统，如未跳转请手动点击</div>
            <a href="<?=htmlspecialchars($order['return_url'])?>" class="back-btn">返回业务系统</a>
        <?php else: ?>
            <a href="pay.php?channel=<?=urlencode($order['channel'] ?? '')?>&pay_order_no=<?=urlencode($pay_order_no)?>&amount=<?=urlencode($order['amount'])?>&subject=<?=urlencode($order['subject'])?>&user_id=<?=urlencode($order['user_id'])?>&biz_sys=<?=urlencode($order['biz_sys'])?>&biz_order_no=<?=urlencode($order['biz_order_no'])?>&notify_url=<?=urlencode($order['notify_url'])?>&return_url=<?=urlencode($order['return_url'])?>&extra=<?=urlencode($order['extra'])?>" class="back-btn">返回通用支付接口</a>
        <?php endif; ?>
    </div>
</body>
</html>