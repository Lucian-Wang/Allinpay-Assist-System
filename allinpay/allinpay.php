<?php
/**
 * 通联支付接口多商户版
 * 支持 priKey 字段为(1)私钥Base64内容 (2)PEM全内容 (3)私钥文件路径
 */

function format_private_key($priKeyOrContent) {
    // 1. 已经是PEM格式内容
    if (strpos($priKeyOrContent, 'BEGIN RSA PRIVATE KEY') !== false || strpos($priKeyOrContent, 'BEGIN PRIVATE KEY') !== false) {
        return $priKeyOrContent;
    }
    // 2. 是物理文件路径
    if (is_file($priKeyOrContent)) {
        return file_get_contents($priKeyOrContent);
    }
    // 3. Base64纯内容，自动拼PEM头尾
    $priKeyBase64 = trim($priKeyOrContent);
    return "-----BEGIN RSA PRIVATE KEY-----\n" . chunk_split($priKeyBase64, 64, "\n") . "-----END RSA PRIVATE KEY-----\n";
}

/**
 * 通联支付下单（多商户版）
 * @param string $paytype W01(微信)/A01(支付宝)/U01(云闪付)
 * @param int $amount 单位:分
 * @param string $order_no 平台订单号
 * @param string $subject 商品描述
 * @param string $appid
 * @param string $cusid
 * @param string $priKeyOrContent
 * @return array [code, payinfo, data, msg]
 */
function allinpay_create_order($paytype, $amount, $order_no, $subject, $appid, $cusid, $priKeyOrContent) {
    $params = [
        'appid'     => $appid,
        'cusid'     => $cusid,
        'paytype'   => $paytype,
        'randomstr' => bin2hex(random_bytes(8)),
        'trxamt'    => $amount,
        'reqsn'     => $order_no,
        'body'      => $subject,
        'signtype'  => 'RSA',
        'version'   => '11'
    ];
    ksort($params);
    $signStr = '';
    foreach ($params as $k => $v) {
        if ($v !== '' && $v !== null && $k != 'sign') $signStr .= $k . '=' . $v . '&';
    }
    $signStr = rtrim($signStr, '&');
    $priKey = format_private_key($priKeyOrContent);
    $privateKey = openssl_get_privatekey($priKey);
    if (!$privateKey) return ['code'=>-1, 'msg'=>'私钥加载失败'];
    $signature = '';
    openssl_sign($signStr, $signature, $privateKey, OPENSSL_ALGO_SHA1);
    unset($privateKey);
    $params['sign'] = base64_encode($signature);

    $ch = curl_init('https://vsp.allinpay.com/apiweb/unitorder/pay');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    if (!$data) return ['code'=>-2, 'msg'=>'通联返回非法', 'raw'=>$response];

    if (isset($data['retcode']) && $data['retcode'] == 'SUCCESS' && isset($data['payinfo'])) {
        return ['code'=>0, 'payinfo'=>$data['payinfo'], 'data'=>$data];
    }
    return ['code'=>-3, 'msg'=>($data['retmsg'] ?? '通联下单失败'), 'raw'=>$data];
}

/**
 * 通联支付查单（多商户版）
 * @param string $order_no 平台订单号
 * @param string $appid
 * @param string $cusid
 * @param string $priKeyOrContent
 * @return array
 */
function allinpay_query_order($order_no, $appid, $cusid, $priKeyOrContent) {
    $params = [
        'appid'     => $appid,
        'cusid'     => $cusid,
        'reqsn'     => $order_no,
        'randomstr' => bin2hex(random_bytes(8)),
        'signtype'  => 'RSA',
        'version'   => '11'
    ];
    ksort($params);
    $signStr = '';
    foreach ($params as $k => $v) {
        if ($v !== '' && $v !== null && $k != 'sign') $signStr .= $k . '=' . $v . '&';
    }
    $signStr = rtrim($signStr, '&');
    $priKey = format_private_key($priKeyOrContent);
    $privateKey = openssl_get_privatekey($priKey);
    if (!$privateKey) return ['retcode'=>'FAIL', 'retmsg'=>'私钥加载失败'];
    $signature = '';
    openssl_sign($signStr, $signature, $privateKey, OPENSSL_ALGO_SHA1);
    unset($privateKey);
    $params['sign'] = base64_encode($signature);

    $ch = curl_init('https://vsp.allinpay.com/apiweb/unitorder/query');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return $data;
}