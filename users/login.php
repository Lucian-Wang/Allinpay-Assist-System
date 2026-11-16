<?php
$page_title = '用户登录';
require_once '../header.php';

if (current_user()) redirect('/users/index.php');
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $token = $_POST['csrf_token'] ?? '';
    if (!csrf_check($token)) $msg = '表单超时，请刷新重试';
    elseif (!$username || !$password) $msg = '请填写完整信息';
    else {
        $db = get_db();
        $stmt = $db->prepare("SELECT * FROM users WHERE username=? OR email=?");
        $stmt->bind_param('ss', $username, $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        if ($user && password_verify_safe($password, $user['password'])) {
            $_SESSION['user'] = ['id'=>$user['id'], 'username'=>$user['username'], 'email'=>$user['email']];
            redirect('/users/index.php');
        } else $msg = '用户名或密码错误';
        $stmt->close();
    }
}
?>
<div class="content-card" style="max-width:420px;margin:48px auto;">
    <div class="card-header">
        <div class="card-title">用户登录</div>
        <div class="card-actions">
            <a href="/users/register.php">没有账号？注册</a>
        </div>
    </div>
    <?php if ($msg): ?>
        <div class="alert" style="margin-bottom:18px;color:#d33;"><?=esc($msg)?></div>
    <?php endif; ?>
    <form method="post" autocomplete="off">
        <div class="form-group" style="margin-bottom:18px;">
            <label>用户名或邮箱</label><br>
            <input type="text" name="username" required style="width:100%;padding:8px;">
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>密码</label><br>
            <input type="password" name="password" required style="width:100%;padding:8px;">
        </div>
        <input type="hidden" name="csrf_token" value="<?=csrf_token()?>">
        <button class="button" type="submit" style="width:100%;">登录</button>
    </form>
</div>
<?php require_once '../footer.php'; ?>