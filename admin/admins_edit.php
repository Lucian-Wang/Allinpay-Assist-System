<?php
require_once 'header.php';
$db = get_admin_db();
$id = intval($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT * FROM admin_users WHERE id=?");
$stmt->execute([$id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$admin) die('管理员不存在');
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = trim($_POST['password'] ?? '');
    if ($password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt2 = $db->prepare("UPDATE admin_users SET password=? WHERE id=?");
        $stmt2->execute([$hash, $id]);
    }
    header('Location: admins.php');
    exit;
}
?>
<div class="content-card" style="max-width:420px;margin:48px auto;">
    <div class="card-header">
        <div class="card-title">编辑管理员</div>
        <div class="card-actions">
            <a href="admins.php">返回</a>
        </div>
    </div>
    <?php if ($msg): ?>
        <div class="alert" style="margin-bottom:18px;color:#d33;"><?=admin_esc($msg)?></div>
    <?php endif; ?>
    <form method="post" autocomplete="off">
        <div class="form-group" style="margin-bottom:18px;">
            <label>用户名</label><br>
            <input type="text" value="<?=admin_esc($admin['username'])?>" disabled style="width:100%;padding:8px;background:#f5f6fa;">
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>新密码（留空则不修改）</label><br>
            <input type="password" name="password" maxlength="50" style="width:100%;padding:8px;">
        </div>
        <button class="button" type="submit" style="width:100%;">保存</button>
    </form>
</div>
<?php require_once 'footer.php'; ?>