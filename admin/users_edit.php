<?php
require_once 'header.php';
$db = get_admin_db();
$id = intval($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) die('用户不存在');
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $now = date('Y-m-d H:i:s');
    if (!$email) $msg = '邮箱不能为空';
    else {
        if ($password) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt2 = $db->prepare("UPDATE users SET email=?, password=?, updated_at=? WHERE id=?");
            $stmt2->execute([$email, $hash, $now, $id]);
        } else {
            $stmt2 = $db->prepare("UPDATE users SET email=?, updated_at=? WHERE id=?");
            $stmt2->execute([$email, $now, $id]);
        }
        header('Location: users.php');
        exit;
    }
}
?>
<div class="content-card" style="max-width:420px;margin:48px auto;">
    <div class="card-header">
        <div class="card-title">编辑用户</div>
        <div class="card-actions">
            <a href="users.php">返回</a>
        </div>
    </div>
    <?php if ($msg): ?>
        <div class="alert" style="margin-bottom:18px;color:#d33;"><?=admin_esc($msg)?></div>
    <?php endif; ?>
    <form method="post" autocomplete="off">
        <div class="form-group" style="margin-bottom:18px;">
            <label>用户名</label><br>
            <input type="text" value="<?=admin_esc($user['username'])?>" disabled style="width:100%;padding:8px;background:#f5f6fa;">
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>邮箱</label><br>
            <input type="email" name="email" value="<?=admin_esc($user['email'])?>" required maxlength="100" style="width:100%;padding:8px;">
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>新密码（留空则不修改）</label><br>
            <input type="password" name="password" maxlength="50" style="width:100%;padding:8px;">
        </div>
        <button class="button" type="submit" style="width:100%;">保存</button>
    </form>
</div>
<?php require_once 'footer.php'; ?>