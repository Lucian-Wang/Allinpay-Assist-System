<?php
header('Content-Type: application/json');
require_once '../../db.php';

// 获取JSON参数
$input = file_get_contents('php://input');
$params = json_decode($input, true);

// 必须参数
$required = ['channel', 'pay_order_no', 'amount', 'subject'];
foreach ($required as $k) {
    if (empty($params[$k])) {
        echo json_encode(['code'=>1, 'msg'=>"缺少参数: $k"]);
        exit;
    }
}
if (!is_numeric($params['amount']) || $params['amount'] < 0.01) {
    echo json_encode(['code'=>1, 'msg'=>"金额错误"]);
    exit;
}

// 查找通道
$db = get_db();
$sql = "SELECT c.*, m.name AS merchant_name FROM commonpay_channels c LEFT JOIN merchants m ON c.merchant_id=m.id WHERE c.channel=? AND c.status=1";
$stmt = $db->prepare($sql);
$stmt->bind_param('s', $params['channel']);
$stmt->execute();
$channelInfo = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$channelInfo) {
    echo json_encode(['code'=>2, 'msg'=>"支付通道不存在或已禁用"]);
    exit;
}

// 检查订单号是否重复
$sql = "SELECT status FROM commonpay_orders WHERE pay_order_no=?";
$stmt = $db->prepare($sql);
$stmt->bind_param('s', $params['pay_order_no']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();
if ($order && $order['status'] === 'paid') {
    echo json_encode(['code'=>4, 'msg'=>"订单已支付"]);
    exit;
}

// 只写一次订单
if (!$order) {
    $notify_url   = $params['notify_url'] ?? '';
    $return_url   = $params['return_url'] ?? '';
    $user_id      = $params['user_id'] ?? '';
    $biz_sys      = $params['biz_sys'] ?? '';
    $biz_order_no = $params['biz_order_no'] ?? '';
    $extra        = $params['extra'] ?? '';

    $sql = "INSERT INTO commonpay_orders 
        (channel_id, merchant_id, pay_order_no, amount, pay_channel, status, subject, notify_url, return_url, user_id, biz_sys, biz_order_no, extra, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
    $stmt = $db->prepare($sql);
    $stmt->bind_param(
        'iisdssssssss',
        $channelInfo['id'],
        $channelInfo['merchant_id'],
        $params['pay_order_no'],
        $params['amount'],
        $params['channel'],
        $params['subject'],
        $notify_url,
        $return_url,
        $user_id,
        $biz_sys,
        $biz_order_no,
        $extra
    );
    $stmt->execute();
    $stmt->close();
}

// 拼装支付链接
$scheme = (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on' ? 'https' : 'http'));
$host = $_SERVER['HTTP_HOST'];
$pay_url = sprintf(
    '%s://%s/commonpay/pay.php?channel=%s&pay_order_no=%s&amount=%s&subject=%s%s%s%s%s%s',
    $scheme,
    $host,
    urlencode($params['channel']),
    urlencode($params['pay_order_no']),
    urlencode($params['amount']),
    urlencode($params['subject']),
    isset($params['user_id']) && $params['user_id'] !== '' ? '&user_id='.urlencode($params['user_id']) : '',
    isset($params['biz_sys']) && $params['biz_sys'] !== '' ? '&biz_sys='.urlencode($params['biz_sys']) : '',
    isset($params['biz_order_no']) && $params['biz_order_no'] !== '' ? '&biz_order_no='.urlencode($params['biz_order_no']) : '',
    isset($params['notify_url']) && $params['notify_url'] !== '' ? '&notify_url='.urlencode($params['notify_url']) : '',
    isset($params['return_url']) && $params['return_url'] !== '' ? '&return_url='.urlencode($params['return_url']) : '',
    isset($params['extra']) && $params['extra'] !== '' ? '&extra='.urlencode($params['extra']) : ''
);

echo json_encode([
    'code'=>0,
    'msg'=>'ok',
    'data'=>[
        'pay_url'=>$pay_url,
        'pay_order_no'=>$params['pay_order_no'],
    ]
]);
exit;