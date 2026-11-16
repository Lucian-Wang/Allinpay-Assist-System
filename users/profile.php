<?php
$page_title = '个人资料';
require_once '../header.php';
require_login();
$user = current_user();
$msg = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = get_db();
    $token = $_POST['csrf_token'] ?? '';

    // 修改邮箱
    if (isset($_POST['email'])) {
        $email = trim($_POST['email'] ?? '');
        if (!csrf_check($token)) $msg = '表单超时，请刷新重试';
        elseif (!$email) $msg = '请填写邮箱';
        else {
            $stmt = $db->prepare("UPDATE users SET email=?, updated_at=NOW() WHERE id=?");
            $stmt->bind_param('si', $email, $user['id']);
            $stmt->execute();
            $_SESSION['user']['email'] = $email;
            $msg = '保存成功';
            $success = true;
            $stmt->close();
        }
    }

    // 修改密码
    if (isset($_POST['old_password'], $_POST['password'], $_POST['password2'])) {
        $old_password = $_POST['old_password'] ?? '';
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';
        if (!csrf_check($token)) $msg = '表单超时，请刷新重试';
        elseif (!$old_password || !$password || !$password2) $msg = '请填写完整信息';
        elseif ($password !== $password2) $msg = '两次新密码不一致';
        else {
            // 验证原密码
            $stmt = $db->prepare("SELECT password FROM users WHERE id=?");
            $stmt->bind_param('i', $user['id']);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if (!$row || !password_verify_safe($old_password, $row['password'])) {
                $msg = '原密码不正确';
            } else {
                $hash = password_hash_safe($password);
                $stmt2 = $db->prepare("UPDATE users SET password=?, updated_at=NOW() WHERE id=?");
                $stmt2->bind_param('si', $hash, $user['id']);
                $stmt2->execute();
                $msg = '密码修改成功';
                $success = true;
                $stmt2->close();
            }
            $stmt->close();
        }
    }
}
?>
<div class="content-card" style="max-width:420px;margin:48px auto;">
    <div class="card-header">
        <div class="card-title">个人资料</div>
        <div class="card-actions">
            <a href="/users/index.php">返回</a>
            <a href="/users/logout.php">退出</a>
        </div>
    </div>
    <?php if ($msg): ?>
        <div class="alert<?= $success ? ' success' : '' ?>" style="margin-bottom:18px;<?= $success ? 'color:#1db34f;' : 'color:#d33;' ?>">
            <?=esc($msg)?>
        </div>
    <?php endif; ?>

    <!-- 修改邮箱表单 -->
    <form method="post" autocomplete="off" style="margin-bottom:38px;">
        <div class="form-group" style="margin-bottom:18px;">
            <label>用户名</label><br>
            <input type="text" value="<?=esc($user['username'])?>" disabled style="width:100%;padding:8px;background:#f5f6fa;">
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>邮箱</label><br>
            <input type="email" name="email" value="<?=esc($user['email'])?>" required style="width:100%;padding:8px;">
        </div>
        <input type="hidden" name="csrf_token" value="<?=csrf_token()?>">
        <button class="button" type="submit" style="width:100%;">保存资料</button>
    </form>

    <!-- 修改密码表单 -->
    <form method="post" autocomplete="off">
        <div style="font-weight:600;color:#222;margin-bottom:12px;font-size:1.08em;">修改密码</div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>当前密码</label><br>
            <input type="password" name="old_password" required maxlength="50" style="width:100%;padding:8px;">
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>新密码</label><br>
            <input type="password" name="password" required maxlength="50" style="width:100%;padding:8px;">
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>重复新密码</label><br>
            <input type="password" name="password2" required maxlength="50" style="width:100%;padding:8px;">
        </div>
        <input type="hidden" name="csrf_token" value="<?=csrf_token()?>">
        <button class="button" type="submit" style="width:100%;">修改密码</button>
    </form>
</div>
<?php require_once '../footer.php'; ?>