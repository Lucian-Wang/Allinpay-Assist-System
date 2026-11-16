<?php
require_once '../db.php';
require_once '../utils.php';
require_login();
$user = current_user();
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $appid = trim($_POST['appid'] ?? '');
    $cusid = trim($_POST['cusid'] ?? '');
    $priKey = trim($_POST['priKey'] ?? '');
    $token = $_POST['csrf_token'] ?? '';
    $pay_type = 'allinpay';
    if (!csrf_check($token)) $msg = '表单超时，请刷新重试';
    elseif (!$name || !$appid || !$cusid || !$priKey) $msg = '请填写完整信息';
    else {
        $db = get_db();
        $now = date('Y-m-d H:i:s');
        $stmt = $db->prepare("INSERT INTO merchants (user_id, name, pay_type, appid, cusid, priKey, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('isssssss', $user['id'], $name, $pay_type, $appid, $cusid, $priKey, $now, $now);
        $stmt->execute();
        redirect('index.php');
    }
}
$page_title = '新建商户';
require_once '../header.php';
?>
<div class="content-card" style="max-width:540px;margin:0 auto;">
    <div class="card-header">
        <div class="card-title">新建商户</div>
        <div class="card-actions">
            <a href="index.php">返回</a>
            <a href="/users/logout.php">退出</a>
        </div>
    </div>
    <?php if ($msg): ?><div class="alert" style="margin-bottom:18px;color:#d33;"><?=esc($msg)?></div><?php endif; ?>
    <form method="post" autocomplete="off">
        <div class="form-group" style="margin-bottom:18px;">
            <label>商户名称</label><br>
            <input type="text" name="name" maxlength="100" required style="width:100%;padding:8px;">
        </div>
        <input type="hidden" name="pay_type" value="allinpay">
        <div class="form-group" style="margin-bottom:18px;">
            <label>AppID</label><br>
            <input type="text" name="appid" maxlength="64" required style="width:100%;padding:8px;">
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>CUSID</label><br>
            <input type="text" name="cusid" maxlength="64" required style="width:100%;padding:8px;">
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>商户私钥PEM内容</label><br>
            <textarea name="priKey" required placeholder="-----BEGIN PRIVATE KEY-----&#10;...&#10;-----END PRIVATE KEY-----" style="width:100%;min-height:120px;font-family:monospace;font-size:14px;padding:8px;border-radius:8px;border:1px solid #e4e7ed;background:#f9fbfd;"></textarea>
            <small>请粘贴完整PEM私钥-----BEGIN PRIVATE KEY-----和-----END PRIVATE KEY-----中间的内容（不带头尾），务必妥善保管。</small>
        </div>
        <input type="hidden" name="csrf_token" value="<?=csrf_token()?>">
        <button class="button" type="submit" style="width:100%;">保存</button>
    </form>
    <div style="margin-top:18px;color:#888;font-size:13px;">
        <b>注意：</b>通联公钥已全局存放，无需重复上传。商户私钥建议复制时注意格式完整。
    </div>
</div>
<?php require_once '../footer.php'; ?>