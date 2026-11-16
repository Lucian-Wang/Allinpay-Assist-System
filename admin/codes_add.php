<?php
require_once 'header.php';
$msg = '';
$db = get_admin_db();

// 生成唯一8位大写字母+数字短码
function random_code($db, $length = 8) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    do {
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $stmt = $db->prepare("SELECT id FROM codes WHERE code=?");
        $stmt->execute([$str]);
        $exists = $stmt->fetch(PDO::FETCH_ASSOC);
    } while ($exists);
    return $str;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id'] ?? 0);
    $merchant_id = intval($_POST['merchant_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $code = random_code($db, 8);
    if (!$user_id || !$merchant_id || !$name) {
        $msg = '请填写完整信息';
    } else {
        $now = date('Y-m-d H:i:s');
        $pay_type = 'allinpay';
        $stmt = $db->prepare("INSERT INTO codes (user_id, merchant_id, name, code, pay_type, created_at, updated_at, status) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([$user_id, $merchant_id, $name, $code, $pay_type, $now, $now]);
        header('Location: codes.php');
        exit;
    }
}
$user_list = $db->query('SELECT id, username FROM users ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
$merchant_list = $db->query('SELECT id, name FROM merchants ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<h2>新建收款码</h2>
<?php if($msg): ?><div style="color:#d33;"><?=admin_esc($msg)?></div><?php endif; ?>
<form method="post" style="max-width:500px;margin:auto;">
    <div class="form-group" style="margin-bottom:18px;">
        <label>归属用户</label><br>
        <select name="user_id" required style="width:100%;padding:8px;">
            <option value="">请选择</option>
            <?php foreach($user_list as $u): ?>
                <option value="<?=$u['id']?>"><?=admin_esc($u['username'])?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group" style="margin-bottom:18px;">
        <label>商户</label><br>
        <select name="merchant_id" required style="width:100%;padding:8px;">
            <option value="">请选择</option>
            <?php foreach($merchant_list as $m): ?>
                <option value="<?=$m['id']?>"><?=admin_esc($m['name'])?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group" style="margin-bottom:18px;">
        <label>卡台名称</label><br>
        <input type="text" name="name" required maxlength="50" style="width:100%;padding:8px;">
    </div>
    <button class="button" type="submit" style="width:100%;">保存</button>
</form>
<div style="margin-top:18px;color:#888;font-size:13px;text-align:center;">
    <b>注意：</b>短码由系统自动生成，用户不可指定。
</div>
<?php require_once 'footer.php'; ?>