<?php
// 通用支付接口异步回调处理（通联支付回调专用）
// 注意：业务回调（notify_url）由本接口处理成功后再主动推送
require_once '../db.php';

// 日志函数：将内容写入到commonpay_notify.log，按日期分文件
function log_notify($message) {
    $log_dir = __DIR__ . '/logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    $log_file = $log_dir . '/commonpay_notify_' . date('Ymd') . '.log';
    $log = '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n";
    file_put_contents($log_file, $log, FILE_APPEND | LOCK_EX);
}

// 通联通知参数获取（根据通联文档适配，key匹配大小写）
$data = $_POST ?: json_decode(file_get_contents('php://input'), true);
log_notify("收到回调: " . json_encode($data, JSON_UNESCAPED_UNICODE));

if (!$data) { 
    log_notify("数据为空，返回fail");
    echo 'fail'; exit; 
}

$pay_order_no = $data['reqsn'] ?? '';
$trxstatus    = $data['trxstatus'] ?? '';
$trxid        = $data['trxid'] ?? '';
$amount       = $data['trxamt'] ?? ''; // 分
$paytime      = $data['fintime'] ?? date('Y-m-d H:i:s');
if (!$pay_order_no) { 
    log_notify("缺少pay_order_no，返回fail");
    echo 'fail'; exit; 
}

$db = get_db();
$stmt = $db->prepare("SELECT * FROM commonpay_orders WHERE pay_order_no=?");
$stmt->bind_param('s', $pay_order_no);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) { 
    log_notify("未找到订单: $pay_order_no，返回fail");
    echo 'fail'; exit; 
}
if ($order['status'] === 'paid') { 
    log_notify("订单已支付: $pay_order_no，返回OK");
    echo 'OK'; exit; 
}

// 判断是否支付成功
if ($trxstatus === '0000') {
    $paid_at = $paytime ? date('Y-m-d H:i:s', strtotime($paytime)) : date('Y-m-d H:i:s');
    $stmt = $db->prepare("UPDATE commonpay_orders SET status='paid', paid_at=?, allinpay_trxid=?, updated_at=NOW() WHERE pay_order_no=?");
    $stmt->bind_param('sss', $paid_at, $trxid, $pay_order_no);
    $stmt->execute(); $stmt->close();

    log_notify("订单支付成功: $pay_order_no, 金额: $amount, 通联流水: $trxid, 时间: $paid_at");

    // 回调业务系统notify_url（如有）
    if ($order['notify_url']) {
        $callback = [
            'pay_order_no' => $pay_order_no,
            'user_id'      => $order['user_id'],
            'amount'       => $order['amount'],
            'status'       => 'paid',
            'paid_at'      => $paid_at,
            'biz_sys'      => $order['biz_sys'],
            'biz_order_no' => $order['biz_order_no'],
            'extra'        => $order['extra']
        ];
        // 签名可根据业务需求自定义，这里留空
        $notify_result = '';
        $curl = curl_init($order['notify_url']);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($callback));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($curl);
        $errno = curl_errno($curl);
        $error = curl_error($curl);
        curl_close($curl);
        $notify_result = $response;
        log_notify("业务系统回调notify_url: {$order['notify_url']}，返回: " . var_export($notify_result, true) . ($errno ? "，curl错误: $errno $error" : ""));

        // 记录业务回调结果
        $stmt = $db->prepare("UPDATE commonpay_orders SET notify_result=? WHERE pay_order_no=?");
        $stmt->bind_param('ss', $notify_result, $pay_order_no);
        $stmt->execute(); $stmt->close();
    }

    echo 'OK'; 
    log_notify("接口处理结束: 返回OK");
    exit;
} else {
    // 未支付或失败
    log_notify("订单未支付或失败: $pay_order_no, trxstatus: $trxstatus，返回fail");
    echo 'fail'; exit;
}