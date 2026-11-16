<?php
require_once '../db.php'; require_once '../utils.php';
$code = $_GET['code'] ?? '';
if (!$code) exit('参数错误');
$db = get_db();
$stmt = $db->prepare("SELECT * FROM codes WHERE code=?");
$stmt->bind_param('s', $code);
$stmt->execute();
$codeinfo = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$codeinfo) exit('收款码不存在！');

function is_wechat() {
    return strpos($_SERVER['HTTP_USER_AGENT'] ?? '', 'MicroMessenger') !== false;
}
function is_alipay() {
    return strpos($_SERVER['HTTP_USER_AGENT'] ?? '', 'AlipayClient') !== false;
}

if (is_wechat() || is_alipay()) {
    // 跳转到支付页面
    header("Location: pay.php?code=" . urlencode($code));
    exit;
}

// 构造扫码入口链接
$domain = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
$link = $domain . "/codes/scan.php?code=" . urlencode($code);
$qrcode_img = "https://lib.zhuli.pro/qrcode.php?data=" . urlencode($link);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>收款台</title>
</head>
<body>
    <div style="margin:60px auto;max-width:340px;padding:32px;background:#fff;border-radius:12px;text-align:center;box-shadow:0 4px 18px #eee;">
        <h2><?=esc($codeinfo['name'])?></h2>
        <p>请用微信或支付宝扫码进行支付</p>
        <img src="<?=$qrcode_img?>" alt="二维码" style="width:220px;height:220px;">
    </div>
</body>
</html>