<?php
require_once '../db.php';
$code = $_GET['code'] ?? '';
$keyword = trim($_GET['keyword'] ?? '');
if (!$code || !$keyword) exit('参数错误');
$db = get_db();
$sql = "SELECT i.*, p.name AS project_name, p.code AS project_code, m.name AS merchant_name
        FROM bills_items i
        LEFT JOIN bills_projects p ON i.project_id=p.id
        LEFT JOIN merchants m ON p.merchant_id=m.id
        WHERE p.code=? AND i.status=1 AND p.status=1 AND i.pay_status=0
        AND (i.customer_tag=? OR i.customer_name=?)";
$stmt = $db->prepare($sql);
$stmt->bind_param('sss', $code, $keyword, $keyword);
$stmt->execute();
$list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (!$list) {
    // 友好提示（与forms风格统一）
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>未查询到账单</title>
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
        <style>
            body {background: #eef4fb;font-family: 'Segoe UI','PingFang SC','Arial',sans-serif;margin:0;padding:0;min-height:100vh;}
            .notfound-container {width:100%;max-width:400px;margin:100px auto 0 auto;background:#fff;border-radius:20px;box-shadow:0 8px 36px rgba(44,104,255,0.10);padding:44px 24px 38px 24px; text-align:center;}
            .icon {font-size:54px;color:#ff9800;margin-bottom:18px;}
            .title {font-size:22px;color:#2d8cf0;font-weight:bold;margin-bottom:12px;}
            .message {font-size:17px;color:#666;margin-bottom:22px;line-height:1.8;}
            .back-btn {display:inline-block;padding:11px 33px;background:linear-gradient(90deg,#1677ff 30%,#269fff 100%);color:#fff;border-radius:10px;text-decoration:none;font-size:17px;}
            .back-btn:hover {background:#135edb;}
        </style>
    </head>
    <body>
        <div class="notfound-container">
            <div class="icon">🔍</div>
            <div class="title">未找到您的账单</div>
            <div class="message">
                很抱歉，未查询到符合条件的账单。<br>
                请确认您的客户标识、手机或姓名填写无误，或联系收款方协助处理。
            </div>
            <a href="javascript:history.back();" class="back-btn">返回重新查询</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>请选择账单</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <style>
        body {background: #eef4fb;font-family: 'Segoe UI','PingFang SC','Arial',sans-serif;margin:0;padding:0;min-height:100vh;}
        .list-container {width:100%;max-width:430px;margin:60px auto 0 auto;background:#fff;border-radius:28px;box-shadow:0 8px 36px rgba(44,104,255,0.10);padding:38px 30px 24px 30px;}
        h3 {font-size:23px;font-weight:600;color:#2d8cf0;text-align:center;margin-bottom:28px;}
        .item {padding:18px 18px 14px 18px;margin:18px 0 0 0;background:#f6fafd;border-radius:14px;box-shadow:0 2px 8px rgba(44,104,255,0.04);}
        .pay-btn {display:inline-block;margin-top:12px;padding:10px 30px;background:linear-gradient(90deg,#1677ff 30%,#269fff 100%);color:#fff;border-radius:10px;text-decoration:none;font-size:17px;font-weight:500;}
        .pay-btn:hover {background:#135edb;}
        .info {font-size:16px;color:#444;line-height:2;margin-bottom:2px;}
        .amount {color:#ff8200;font-weight:600;font-size:18px;}
        .tips {color:#FF0000;font-weight:600;font-size:18px;}
    </style>
</head>
<body>
<div class="list-container">
    <h3>请选择要支付的账单</h3>
    <?php foreach($list as $row): ?>
        <div class="item">
            <div class="info">客户：<?=htmlspecialchars($row['customer_name'])?></div>
            <div class="info">标识：<?=htmlspecialchars($row['customer_tag'])?></div>
            <div class="info">账单：<?=htmlspecialchars($row['remark'])?></div>
            <div class="info">金额：<span class="amount"><?=number_format($row['amount'],2)?></span> 元</div>
            <div class="info"><span class="tips">提醒：请您认真仔细核对信息和金额，如有错误，请勿支付。</span></div>
            <a class="pay-btn" href="pay.php?code=<?=urlencode($row['project_code'])?>&item_id=<?=$row['id']?>">去支付</a>
        </div>
    <?php endforeach;?>
</div>
</body>
</html>