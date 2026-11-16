<?php
// 用于计划任务：定时批量查未支付订单，主动查通联支付状态，自动更新及业务回调
require_once dirname(__DIR__).'/db.php';
require_once dirname(__DIR__).'/allinpay/allinpay.php';

$db = get_db();

// 1. 查询最近20分钟内未支付的订单（只查未支付的，且商户参数齐全）
$sql = "SELECT o.pay_order_no, o.id, o.notify_url, o.user_id, o.amount, o.biz_sys, o.biz_order_no, o.extra, m.appid, m.cusid, m.priKey 
        FROM commonpay_orders o 
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
    $count++;
    $pay_order_no = $order['pay_order_no'];
    $appid = $order['appid'];
    $cusid = $order['cusid'];
    $priKey = $order['priKey'];

    // 查通联
    $result = allinpay_query_order($pay_order_no, $appid, $cusid, $priKey);

    // 只要trxstatus为0000就认为已支付
    if (
        $result
        && isset($result['retcode']) && $result['retcode'] === 'SUCCESS'
        && isset($result['trxstatus']) && $result['trxstatus'] === '0000'
    ) {
        $now = date('Y-m-d H:i:s');
        $trxid = $result['trxid'] ?? '';
        // 更新订单状态
        $stmt2 = $db->prepare("UPDATE commonpay_orders SET status='paid', paid_at=?, updated_at=?, allinpay_trxid=? WHERE pay_order_no=?");
        $stmt2->bind_param('ssss', $now, $now, $trxid, $pay_order_no);
        $stmt2->execute();
        $stmt2->close();

        // 主动回调业务系统
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

        $success++;
        echo date('Y-m-d H:i:s')." Order $pay_order_no paid, trxid=$trxid\n";
    } else {
        $failed++;
        echo date('Y-m-d H:i:s')." Order $pay_order_no not paid\n";
    }

    // 防止接口压力过大，每查一个单sleep 0.5秒
    usleep(500000);
}

echo "Checked $count orders, $success paid, $failed unpaid.\n";