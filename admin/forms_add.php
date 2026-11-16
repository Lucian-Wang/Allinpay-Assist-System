<?php
$page_title = '新建表单';
require_once 'header.php';
$db = get_admin_db();
$msg = '';

// 生成唯一8位大写字母+数字短码
function random_code($db, $length = 8) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    do {
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $stmt = $db->prepare("SELECT id FROM forms WHERE code=?");
        $stmt->execute([$str]);
        $exists = $stmt->fetch(PDO::FETCH_ASSOC);
    } while ($exists);
    return $str;
}

// 商户下拉
$merchants = $db->query("SELECT id, name FROM merchants ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
// 用户下拉
$users = $db->query("SELECT id, username FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['desc'] ?? '');
    $merchant_id = intval($_POST['merchant_id'] ?? 0);
    $code = random_code($db, 8);
    if (!$user_id || !$name || !$merchant_id) $msg = '请填写完整信息';
    else {
        $now = date('Y-m-d H:i:s');
        $stmt = $db->prepare("INSERT INTO forms (user_id, merchant_id, name, `desc`, code, created_at, updated_at, status) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([$user_id, $merchant_id, $name, $desc, $code, $now, $now]);
        header('Location: forms.php');
        exit;
    }
}
?>
<div class="content-card" style="max-width:600px;margin:48px auto;">
    <div class="card-header" style="margin-bottom:20px;">
        <span class="card-title">新建表单</span>
        <span class="card-actions"><a href="forms.php">返回</a></span>
    </div>
    <?php if($msg): ?><div style="color:red;"><?=$msg?></div><?php endif;?>
    <form method="post" style="padding:0 8px;">
        <div class="form-group" style="margin-bottom:18px;">
            <label>归属用户*</label>
            <select name="user_id" required class="input">
                <option value="">请选择</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?=$u['id']?>"><?=admin_esc($u['username'])?>(<?=$u['id']?>)</option>
                <?php endforeach;?>
            </select>
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>表单名称*</label>
            <input type="text" name="name" required class="input" autocomplete="off" placeholder="请输入表单名称">
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>表单描述</label>
            <input type="text" name="desc" class="input" autocomplete="off" placeholder="请输入描述">
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>选择商户*</label>
            <select name="merchant_id" required class="input">
                <option value="">请选择</option>
                <?php foreach ($merchants as $m): ?>
                    <option value="<?=$m['id']?>"><?=admin_esc($m['name'])?>(<?=$m['id']?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <!-- 短码由系统自动生成，用户不可见不可输入 -->
        <button type="submit" class="button">保存</button>
    </form>
</div>
<?php require_once 'footer.php'; ?>