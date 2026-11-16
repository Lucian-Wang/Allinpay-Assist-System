<?php
/**
 * 获取当前支付环境
 * - wechat: 微信内
 * - alipay: 支付宝内
 * - other: 其它
 */
function get_pay_env() {
    $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    if (strpos($ua, 'micromessenger') !== false) return 'wechat';
    if (strpos($ua, 'alipayclient') !== false) return 'alipay';
    return 'other';
}