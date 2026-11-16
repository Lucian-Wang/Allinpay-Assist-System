<?php
$page_title = '支付链接拼装生成器';
require_once '../header.php';
require_login();
$user = current_user();
$db = get_db();
// 获取自己的通道
$stmt = $db->prepare("SELECT c.id, c.channel, c.name, m.name AS merchant_name FROM commonpay_channels c LEFT JOIN merchants m ON c.merchant_id=m.id WHERE m.user_id=? AND c.status=1 ORDER BY c.id DESC");
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$channels = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function h($s) {return htmlspecialchars($s, ENT_QUOTES);}

$link = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $channel = trim($_POST['channel'] ?? '');
    $pay_order_no = trim($_POST['pay_order_no'] ?? '');
    $amount = trim($_POST['amount'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $user_id = trim($_POST['user_id'] ?? '');
    $biz_sys = trim($_POST['biz_sys'] ?? '');
    $biz_order_no = trim($_POST['biz_order_no'] ?? '');
    $notify_url = trim($_POST['notify_url'] ?? '');
    $return_url = trim($_POST['return_url'] ?? '');
    $extra = trim($_POST['extra'] ?? '');

    if($channel && $pay_order_no && $amount && $subject) {
        $params = [
            'channel' => $channel,
            'pay_order_no' => $pay_order_no,
            'amount' => $amount,
            'subject' => $subject, // 不要 urlencode，让 http_build_query 自动处理
        ];
        if ($user_id) $params['user_id'] = $user_id;
        if ($biz_sys) $params['biz_sys'] = $biz_sys;
        if ($biz_order_no) $params['biz_order_no'] = $biz_order_no;
        if ($notify_url) $params['notify_url'] = $notify_url;
        if ($return_url) $params['return_url'] = $return_url;
        if ($extra) $params['extra'] = $extra;
        $query = http_build_query($params);
        $link = 'https://allinpay.zhuli.pro/commonpay/pay.php?' . $query;
    }
}
?>
<style>
    /* 美化所有输入框，包括 select 和 number */
    .content-card select,
    .content-card input[type="text"],
    .content-card input[type="number"] {
        width: 100%;
        padding: 8px;
        border: 1px solid #e5e6eb;
        border-radius: 8px;
        background: #f7fafd;
        font-size: 15px;
        outline: none;
        transition: border-color .2s;
        box-sizing: border-box;
    }
    .content-card select:focus,
    .content-card input[type="text"]:focus,
    .content-card input[type="number"]:focus {
        border-color: #3183f4;
        background: #fff;
    }
</style>
<div class="content-card" style="max-width:700px;margin:48px auto;">
    <div class="card-header">
        <div class="card-title">支付链接拼装生成器</div>
        <div class="card-actions">
            <a href="help.php">接口说明</a>
            <a href="index.php">通道管理</a>
        </div>
    </div>
    <form method="post" style="padding:20px 0;">
        <div class="form-group" style="margin-bottom:18px;">
            <label>通道</label>
            <select name="channel" required>
                <option value="">请选择</option>
                <?php foreach($channels as $c): ?>
                <option value="<?=h($c['channel'])?>" <?=isset($_POST['channel'])&&$_POST['channel']==$c['channel']?'selected':''?>>
                    <?=h($c['channel'])?>（<?=h($c['name'])?>, 商户:<?=h($c['merchant_name'])?>）
                </option>
                <?php endforeach;?>
            </select>
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>平台支付订单号 <span style="color:#aaa;">(pay_order_no，需唯一)</span></label>
            <input type="text" name="pay_order_no" value="<?=h($_POST['pay_order_no']??'BIZ'.date('YmdHis').rand(100,999))?>" required>
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>金额(元)</label>
            <input type="number" name="amount" min="0.01" step="0.01" value="<?=h($_POST['amount']??'0.01')?>" required>
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>商品名称 <span style="color:#aaa;">(subject)</span></label>
            <input type="text" name="subject" maxlength="64" value="<?=h($_POST['subject']??'测试商品')?>" required>
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>业务用户ID <span style="color:#aaa;">(user_id，可选)</span></label>
            <input type="text" name="user_id" value="<?=h($_POST['user_id']??'')?>">
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>业务系统标识 <span style="color:#aaa;">(biz_sys，可选)</span></label>
            <input type="text" name="biz_sys" value="<?=h($_POST['biz_sys']??'')?>">
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>业务订单号 <span style="color:#aaa;">(biz_order_no，可选)</span></label>
            <input type="text" name="biz_order_no" value="<?=h($_POST['biz_order_no']??'')?>">
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>回调通知地址 <span style="color:#aaa;">(notify_url，可选)</span></label>
            <input type="text" name="notify_url" value="<?=h($_POST['notify_url']??'')?>" placeholder="https://...">
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>前端回跳地址 <span style="color:#aaa;">(return_url，可选)</span></label>
            <input type="text" name="return_url" value="<?=h($_POST['return_url']??'')?>" placeholder="https://...">
        </div>
        <div class="form-group" style="margin-bottom:24px;">
            <label>扩展参数 <span style="color:#aaa;">(extra，可选)</span></label>
            <input type="text" name="extra" value="<?=h($_POST['extra']??'')?>">
        </div>
        <button class="button" type="submit" style="width:100%;">生成支付链接</button>
    </form>
    <?php if($link): ?>
    <div style="background:#f6ffec;border:1px solid #b7eb8f;padding:15px 16px;word-break:break-all;">
        <strong>支付链接：</strong><br>
        <input type="text" value="<?=h($link)?>" readonly style="width:100%;padding:8px;font-size:14px;">
        <div style="margin-top:10px;">
            <a href="<?=$link?>" target="_blank" style="color:#3183f4;">[ 打开支付链接 ]</a>
        </div>
    </div>
    <?php endif;?>
    <div style="margin-top:32px;color:#888;font-size:13px;">
        * 生成的支付链接建议用二维码工具转为二维码，便于手机扫码体验。参数说明请见 <a href="help.php">接口说明</a>。
    </div>
</div>
<?php require_once '../footer.php'; ?>