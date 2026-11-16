<?php
/**
 * 通联支付SDK - 支持多商户（私钥每个商户单独存DB，公钥全局唯一）
 * 自动适配扫码、微信/支付宝H5、JSAPI等主流聚合支付场景。
 * 
 * 用法例子：
 *   $merchant = [
 *     'appid'  => 'xxx',
 *     'cusid'  => 'xxx',
 *     'priKey' => '-----BEGIN PRIVATE KEY----- ... -----END PRIVATE KEY-----' // 数据库取出
 *   ];
 *   $client = new AllInPayClient($merchant);
 *   $res = $client->createOrder('wxh5', 100, 'ORDER123', '商品', ['return_url'=>'https://xx.com/ok', ...]);
 */

class AllInPayClient {
    private $appid;
    private $cusid;
    private $priKey; // PEM内容
    private $pubKeyPath = '/www/wwwroot/allinpay.zhuli.pro/certs/allinpay_public.pem';

    /**
     * @param array $config ['appid'=>, 'cusid'=>, 'priKey'=>, 'pubKeyPath'=>可选]
     */
    public function __construct($config) {
        $this->appid = $config['appid'];
        $this->cusid = $config['cusid'];
        $this->priKey = $config['priKey'];
        if (!empty($config['pubKeyPath'])) $this->pubKeyPath = $config['pubKeyPath'];
    }

    /**
     * 下单
     * @param string $paytype W01(微信扫码)/A01(支付宝扫码)/U01(云闪付扫码)/wxh5(alih5)/JSAPI等
     * @param int $amount    单位:分
     * @param string $order_no  平台订单号
     * @param string $subject   商品描述
     * @param array $opts  额外参数，如openid、return_url、client_ip等
     * @return array [code, payinfo, data, msg]
     */
    public function createOrder($paytype, $amount, $order_no, $subject, $opts = []) {
        $params = [
            'appid'     => $this->appid,
            'cusid'     => $this->cusid,
            'paytype'   => $paytype,
            'randomstr' => bin2hex(random_bytes(8)),
            'trxamt'    => $amount,
            'reqsn'     => $order_no,
            'body'      => $subject,
            'signtype'  => 'RSA',
            'version'   => '11',
        ];
        // H5/JSAPI等场景需传递以下字段
        if (!empty($opts['openid'])) $params['openid'] = $opts['openid'];
        if (!empty($opts['return_url'])) $params['returl'] = $opts['return_url'];
        if (!empty($opts['client_ip'])) $params['clientip'] = $opts['client_ip'];
        if (!empty($opts['sub_appid'])) $params['sub_appid'] = $opts['sub_appid'];
        // 其它业务参数可继续扩展

        ksort($params);
        $signStr = $this->getSignContent($params);
        $privateKey = openssl_get_privatekey($this->priKey);
        if (!$privateKey) return ['code'=>-1, 'msg'=>'私钥加载失败'];
        $signature = '';
        openssl_sign($signStr, $signature, $privateKey, OPENSSL_ALGO_SHA1);
        unset($privateKey);
        $params['sign'] = base64_encode($signature);

        $url = 'https://vsp.allinpay.com/apiweb/unitorder/pay';
        $response = $this->curlPost($url, $params);
        $data = json_decode($response, true);
        if (!$data) return ['code'=>-2, 'msg'=>'通联返回非法', 'raw'=>$response];
        if (isset($data['retcode']) && $data['retcode'] == 'SUCCESS' && isset($data['payinfo'])) {
            return ['code'=>0, 'payinfo'=>$data['payinfo'], 'data'=>$data];
        }
        return ['code'=>-3, 'msg'=>($data['retmsg'] ?? '通联下单失败'), 'raw'=>$data];
    }

    /**
     * 查单
     * @param string $order_no
     * @return array
     */
    public function queryOrder($order_no) {
        $params = [
            'appid'     => $this->appid,
            'cusid'     => $this->cusid,
            'reqsn'     => $order_no,
            'randomstr' => bin2hex(random_bytes(8)),
            'signtype'  => 'RSA',
            'version'   => '11'
        ];
        ksort($params);
        $signStr = $this->getSignContent($params);
        $privateKey = openssl_get_privatekey($this->priKey);
        if (!$privateKey) return ['retcode'=>'FAIL', 'retmsg'=>'私钥加载失败'];
        $signature = '';
        openssl_sign($signStr, $signature, $privateKey, OPENSSL_ALGO_SHA1);
        unset($privateKey);
        $params['sign'] = base64_encode($signature);

        $url = 'https://vsp.allinpay.com/apiweb/unitorder/query';
        $response = $this->curlPost($url, $params);
        $data = json_decode($response, true);
        return $data;
    }

    /**
     * 回调验签
     * @param array $data
     * @return bool
     */
    public function verifyNotify($data) {
        if (!isset($data['sign'])) return false;
        $sign = $data['sign'];
        unset($data['sign']);
        ksort($data);
        $verifyStr = $this->getSignContent($data);
        $allinpayPub = file_get_contents($this->pubKeyPath);
        $publicKey = openssl_get_publickey($allinpayPub);
        $verifyResult = openssl_verify($verifyStr, base64_decode($sign), $publicKey, OPENSSL_ALGO_SHA1);
        unset($publicKey);
        return $verifyResult === 1;
    }

    /**
     * 组装签名内容
     */
    private function getSignContent($arr) {
        $signStr = '';
        foreach ($arr as $k => $v) {
            if ($v !== '' && $v !== null && $k != 'sign') $signStr .= $k . '=' . $v . '&';
        }
        return rtrim($signStr, '&');
    }

    /**
     * POST请求
     */
    private function curlPost($url, $params) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
}