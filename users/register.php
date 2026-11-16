<?php
$page_title = '用户注册';
require_once '../header.php';

if (current_user()) redirect('/users/index.php');
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    $token = $_POST['csrf_token'] ?? '';
    if (!csrf_check($token)) $msg = '表单超时，请刷新重试';
    elseif (!$username || !$email || !$password) $msg = '请填写完整信息';
    elseif ($password !== $password2) $msg = '两次密码不一致';
    else {
        $db = get_db();
        $stmt = $db->prepare("SELECT id FROM users WHERE username=? OR email=?");
        $stmt->bind_param('ss', $username, $email);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) $msg = '用户名或邮箱已存在';
        else {
            $now = date('Y-m-d H:i:s');
            $hash = password_hash_safe($password);
            $stmt2 = $db->prepare("INSERT INTO users (username, password, email, created_at, updated_at) VALUES (?, ?, ?, ?, ?)");
            $stmt2->bind_param('sssss', $username, $hash, $email, $now, $now);
            $stmt2->execute();
            $_SESSION['user'] = ['id'=>$db->insert_id, 'username'=>$username, 'email'=>$email];
            redirect('/users/index.php');
        }
        $stmt->close();
    }
}
?>
<div class="content-card" style="max-width:420px;margin:48px auto;">
    <div class="card-header">
        <div class="card-title">用户注册</div>
        <div class="card-actions">
            <a href="/users/login.php">已有账号？登录</a>
        </div>
    </div>
    <?php if ($msg): ?>
        <div class="alert" style="margin-bottom:18px;color:#d33;"><?=esc($msg)?></div>
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
        <input type="hidden" name="csrf_token" value="<?=csrf_token()?>">
        <button class="button" type="submit" style="width:100%;">注册</button>
    </form>
</div>
<?php require_once '../footer.php'; ?>