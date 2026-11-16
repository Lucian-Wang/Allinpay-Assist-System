<?php
// 支持的支付方式，前端可拉取展示
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'code'=>0,
    'msg'=>'ok',
    'pay_types'=>[
        ['id'=>'W01', 'name'=>'微信扫码'],
        ['id'=>'A01', 'name'=>'支付宝扫码'],
        ['id'=>'U01', 'name'=>'云闪付扫码'],
        ['id'=>'wxh5', 'name'=>'微信内H5'],
        ['id'=>'alih5', 'name'=>'支付宝内H5'],
    ]
]);
exit;