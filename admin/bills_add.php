<?php
$page_title = '新建账单项目';
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
        $stmt = $db->prepare("SELECT id FROM bills_projects WHERE code=?");
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
    $merchant_id = intval($_POST['merchant_id'] ?? 0);
    $desc = trim($_POST['desc'] ?? '');
    $status = intval($_POST['status'] ?? 1);
    $code = random_code($db, 8);
    if (!$user_id || !$name || !$merchant_id) $msg = '请填写完整信息';
    else {
        $now = date('Y-m-d H:i:s');
        $stmt = $db->prepare("INSERT INTO bills_projects (user_id, merchant_id, name, code, `desc`, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $merchant_id, $name, $code, $desc, $status, $now, $now]);
        header('Location: bills.php');
        exit;
    }
}
?>
<div class="content-card" style="max-width:420px;margin:48px auto;">
    <div class="card-header">
        <div class="card-title">新建账单项目</div>
        <div class="card-actions"><a href="bills.php">返回</a></div>
    </div>
    <?php if ($msg): ?><div class="alert" style="color:#d33;"><?=admin_esc($msg)?></div><?php endif; ?>
    <form method="post" autocomplete="off">
        <div class="form-group" style="margin-bottom:18px;">
            <label>归属用户</label>
            <select name="user_id" required style="width:100%;padding:8px;">
                <option value="">请选择用户</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?=$u['id']?>"><?=admin_esc($u['username'])?>(<?=$u['id']?>)</option>
                <?php endforeach;?>
            </select>
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>项目名称</label>
            <input type="text" name="name" required maxlength="100" style="width:100%;padding:8px;">
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>选择商户</label>
            <select name="merchant_id" required style="width:100%;padding:8px;">
                <option value="">请选择商户</option>
                <?php foreach ($merchants as $m): ?>
                <option value="<?=$m['id']?>"><?=admin_esc($m['name'])?>(<?=$m['id']?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>描述</label>
            <input type="text" name="desc" maxlength="255" style="width:100%;padding:8px;">
        </div>
        <!-- 短码由系统自动生成，用户不可见不可输入 -->
        <button class="button" type="submit" style="width:100%;">保存</button>
    </form>
</div>
<?php require_once 'footer.php'; ?>