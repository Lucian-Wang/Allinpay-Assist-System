<?php
require_once 'header.php';
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    $db = get_admin_db();
    if (!$username || !$email || !$password) $msg = '请填写完整信息';
    elseif ($password !== $password2) $msg = '两次密码不一致';
    else {
        $stmt = $db->prepare("SELECT id FROM users WHERE username=? OR email=?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) $msg = '用户名或邮箱已存在';
        else {
            $now = date('Y-m-d H:i:s');
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt2 = $db->prepare("INSERT INTO users (username, password, email, created_at, updated_at) VALUES (?, ?, ?, ?, ?)");
            $stmt2->execute([$username, $hash, $email, $now, $now]);
            header('Location: users.php');
            exit;
        }
    }
}
?>
<div class="content-card" style="max-width:420px;margin:48px auto;">
    <div class="card-header">
        <div class="card-title">新建用户</div>
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
            <input type="text" name="username" required maxlength="50" style="width:100%;padding:8px;">
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>邮箱</label><br>
            <input type="email" name="email" required maxlength="100" style="width:100%;padding:8px;">
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>密码</label><br>
            <input type="password" name="password" required maxlength="50" style="width:100%;padding:8px;">
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>重复密码</label><br>
            <input type="password" name="password2" required maxlength="50" style="width:100%;padding:8px;">
        </div>
        <button class="button" type="submit" style="width:100%;">保存</button>
    </form>
</div>
<?php require_once 'footer.php'; ?>