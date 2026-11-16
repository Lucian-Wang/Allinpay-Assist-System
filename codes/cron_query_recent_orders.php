<?php
// 用于cron定时任务：每5分钟执行一次，检查最近20分钟内未支付的订单，并主动查通联支付状态

require_once '/www/wwwroot/allinpay.zhuli.pro/db.php';
require_once '/www/wwwroot/allinpay.zhuli.pro/allinpay/allinpay.php';

// 数据库连接
$db = get_db();

// 1. 查询最近20分钟内未支付的订单（只查未支付的，且商户参数齐全）
$sql = "SELECT o.order_no, o.id, m.appid, m.cusid, m.priKey 
        FROM code_orders o 
        LEFT JOIN merchants m ON o.merchant_id = m.id 
        WHERE o.status='pending' 
          AND o.created_at >= DATE_SUB(NOW(), INTERVAL 20 MINUTE)
          AND m.appid IS NOT NULL AND m.cusid IS NOT NULL AND m.priKey IS NOT NULL
        LIMIT 100";
$res = $db->query($sql);

$count = 0;
$success = 0;
$failed = 0;
while ($order = $res->fetch_assoc()) {
    $order_no = $order['order_no'];
    $appid = $order['appid'];
    $cusid = $order['cusid'];
    $priKey = $order['priKey'];

    $count++;

    // 查通联
    $result = allinpay_query_order($order_no, $appid, $cusid, $priKey);

    // 只要trxstatus为0000就认为已支付
    if (
        $result
        && isset($result['retcode']) && $result['retcode'] === 'SUCCESS'
        && isset($result['trxstatus']) && $result['trxstatus'] === '0000'
    ) {
        $now = date('Y-m-d H:i:s');
        $trxid = $result['trxid'] ?? '';
        $stmt2 = $db->prepare("UPDATE code_orders SET status='paid', paid_at=?, updated_at=?, trxid=? WHERE order_no=?");
        $stmt2->bind_param('ssss', $now, $now, $trxid, $order_no);
        $stmt2->execute();
        $stmt2->close();
        $success++;
        echo "Order $order_no paid, trxid=$trxid\n";
    } else {
        $failed++;
        echo "Order $order_no not paid\n";
    }

    // 防止接口压力过大，每查一个单sleep 0.5秒
    usleep(500000);
}

echo "Checked $count orders, $success paid, $failed unpaid.\n";