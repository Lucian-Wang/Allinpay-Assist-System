<?php
require_once 'header.php';
$db = get_admin_db();
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id']);
    $name = trim($_POST['name'] ?? '');
    $appid = trim($_POST['appid'] ?? '');
    $cusid = trim($_POST['cusid'] ?? '');
    $priKey = trim($_POST['priKey'] ?? '');
    $pay_type = 'allinpay';
    if (!$user_id || !$name || !$appid || !$cusid || !$priKey) {
        $msg = '请填写完整信息';
    } else {
        $now = date('Y-m-d H:i:s');
        $stmt = $db->prepare("INSERT INTO merchants (user_id, name, pay_type, appid, cusid, priKey, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $name, $pay_type, $appid, $cusid, $priKey, $now, $now]);
        header('Location: merchants.php');
        exit;
    }
}
$users = $db->query("SELECT id,username FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<h2>新建商户</h2>
<div class="content-card" style="max-width:540px;margin:0 auto;">
    <?php if ($msg): ?><div class="alert" style="margin-bottom:18px;color:#d33;"><?=admin_esc($msg)?></div><?php endif; ?>
    <form method="post" autocomplete="off">
        <div class="form-group" style="margin-bottom:18px;">
            <label>归属用户</label><br>
            <select name="user_id" required style="width:100%;padding:8px;">
                <option value="">请选择用户</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?=$u['id']?>"><?=admin_esc($u['username'])?>(<?=$u['id']?>)</option>
                <?php endforeach;?>
            </select>
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>商户名称</label><br>
            <input type="text" name="name" maxlength="100" required style="width:100%;padding:8px;">
        </div>
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
            <small>请粘贴完整PEM私钥，不带头尾，务必妥善保管。</small>
        </div>
        <button class="button" type="submit" style="width:100%;">保存</button>
    </form>
</div>
<?php require_once 'footer.php'; ?>