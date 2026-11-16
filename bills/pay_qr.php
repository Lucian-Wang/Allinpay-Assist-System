<?php
require_once '../db.php';

$order_no = $_GET['order_no'] ?? '';
if (!$order_no) exit('缺少订单号参数');

// 查询订单，payinfo 必须存在
$db = get_db();
$sql = "SELECT o.*, i.remark, i.amount, i.customer_name, i.customer_tag, p.name AS project_name, p.code AS project_code, m.name AS merchant_name 
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
if (empty($order['payinfo'])) exit('未获取到支付链接');

$payinfo = $order['payinfo'];
$amount = number_format($order['amount'], 2);

function get_pay_env() {
    $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    if (strpos($ua, 'micromessenger') !== false) return 'wechat';
    if (strpos($ua, 'alipayclient') !== false) return 'alipay';
    return 'pc';
}
$pay_env = get_pay_env();

// 手机端直接跳转支付链接
if ($pay_env === 'wechat' || $pay_env === 'alipay') {
    header("Location: {$payinfo}");
    exit;
}

// PC端才展示二维码
$scan_tip = ($order['pay_channel'] === 'W01')
    ? '请使用<strong style="color:#1c8;">微信</strong>扫一扫支付'
    : '请使用<strong style="color:#1677ff;">支付宝</strong>扫一扫支付';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>扫码支付</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <style>
        body {background: linear-gradient(135deg, #eaf2fe 0%, #f5f7fa 100%);font-family: 'Segoe UI','PingFang SC','Arial',sans-serif;margin:0;padding:0;min-height:100vh;}
        .pay-container {width:100%;max-width:370px;margin:60px auto 0 auto;background:#fff;border-radius:20px;box-shadow:0 8px 36px rgba(44,104,255,0.10);padding:36px 24px 18px 24px;}
        .scan-tip {font-size:18px;margin-bottom:18px;color:#2d8cf0;font-weight:500;letter-spacing:1px;text-align:center;}
        .qr {display: flex; justify-content: center; align-items: center; margin:0 auto 16px auto;background:#f7fafd;padding:18px;border-radius:14px;box-shadow:0 2px 10px rgba(44,104,255,0.07);}
        .info {font-size:15px;color:#555;margin-top:16px;word-break:break-all;text-align:center;}
        .back-btn {display:inline-block;margin-top:28px;padding:10px 32px;background:linear-gradient(90deg,#1677ff 30%,#269fff 100%);color:#fff;border-radius:8px;text-decoration:none;font-size:16px;transition:background 0.18s;}
        .back-btn:hover {background:#135edb;}
        @media (max-width:500px) {.pay-container {margin:18px auto 0 auto;padding:15px 3vw 12px 3vw;}}
    </style>
    <script>
    setInterval(function(){
        fetch("query_order.php?order_no=<?=urlencode($order_no)?>")
        .then(r=>r.json()).then(d=>{
            if(d.status === 'paid'){
                window.location.href = "pay_result.php?order_no=<?=urlencode($order_no)?>";
            }
        });
    }, 2000);
    </script>
</head>
<body>
    <div class="pay-container">
        <div class="scan-tip"><?=$scan_tip?></div>
        <div class="qr">
            <img src="https://lib.zhuli.pro/qrcode.php?data=<?=urlencode($payinfo)?>" width="240" height="240" alt="二维码">
        </div>
        <div class="info">
            客户：<?=htmlspecialchars($order['customer_name'])?><?php if($order['customer_tag']) echo ' ('.htmlspecialchars($order['customer_tag']).')'?><br>
            金额：<?=$amount?> 元<br>
            账单：<?=htmlspecialchars($order['remark'])?><br>
            订单号：<?=htmlspecialchars($order_no)?><br>
            <a href="pay.php?code=<?=urlencode($order['project_code'])?>&item_id=<?=urlencode($order['item_id'])?>" class="back-btn">返回收银台</a>
        </div>
    </div>
</body>
</html>