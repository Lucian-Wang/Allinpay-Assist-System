<?php
require_once 'header.php';
$db = get_admin_db();
$id = intval($_GET['id'] ?? 0);
$stmt = $db->prepare('SELECT * FROM codes WHERE id=?');
$stmt->execute([$id]);
$info = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$info) die('收款码不存在');
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id'] ?? 0);
    $merchant_id = intval($_POST['merchant_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    if (!$user_id || !$merchant_id || !$name) {
        $msg = '请填写完整信息';
    } else {
        $now = date('Y-m-d H:i:s');
        $stmt = $db->prepare('UPDATE codes SET user_id=?, merchant_id=?, name=?, updated_at=? WHERE id=?');
        $stmt->execute([$user_id, $merchant_id, $name, $now, $id]);
        header('Location: codes.php');
        exit;
    }
}
$user_list = $db->query('SELECT id, username FROM users ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
$merchant_list = $db->query('SELECT id, name FROM merchants ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<h2>编辑收款码</h2>
<?php if($msg): ?><div style="color:#d33;"><?=admin_esc($msg)?></div><?php endif; ?>
<form method="post" style="max-width:500px;margin:auto;">
    <div class="form-group" style="margin-bottom:18px;">
        <label>归属用户</label><br>
        <select name="user_id" required style="width:100%;padding:8px;">
            <option value="">请选择</option>
            <?php foreach($user_list as $u): ?>
                <option value="<?=$u['id']?>" <?=$info['user_id']==$u['id']?'selected':''?>><?=admin_esc($u['username'])?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group" style="margin-bottom:18px;">
        <label>商户</label><br>
        <select name="merchant_id" required style="width:100%;padding:8px;">
            <option value="">请选择</option>
            <?php foreach($merchant_list as $m): ?>
                <option value="<?=$m['id']?>" <?=$info['merchant_id']==$m['id']?'selected':''?>><?=admin_esc($m['name'])?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group" style="margin-bottom:18px;">
        <label>卡台名称</label><br>
        <input type="text" name="name" maxlength="50" required value="<?=admin_esc($info['name'])?>" style="width:100%;padding:8px;">
    </div>
    <div class="form-group" style="margin-bottom:18px;">
        <label>短码</label><br>
        <input type="text" value="<?=admin_esc($info['code'])?>" style="width:100%;padding:8px;background:#f5f6fa;color:#999;cursor:not-allowed;" readonly>
    </div>
    <button class="button" type="submit" style="width:100%;">保存</button>
</form>
<div style="margin-top:18px;color:#888;font-size:13px;text-align:center;">
    <b>注意：</b>短码不可修改。
</div>
<?php require_once 'footer.php'; ?>