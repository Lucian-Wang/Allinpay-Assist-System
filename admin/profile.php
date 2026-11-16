<?php
require_once 'functions.php';
require_once __DIR__.'/../utils.php';
require_once 'config.php';

if (session_status() == PHP_SESSION_NONE) session_start();
require_admin_login();
$admin = function_exists('current_admin') ? current_admin() : admin_current_user();

$msg = '';
$success = false;

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    $id = $admin['id'];

    if ($password && $password !== $password2) {
        $msg = '两次输入的密码不一致';
    } elseif ($password) {
        $db = get_admin_db();
        $stmt = $db->prepare("UPDATE admin_users SET password=? WHERE id=?");
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt->execute([$hashed, $id]);
        $success = true;
        $msg = '密码已成功修改';
    } else {
        $msg = '未做任何更改';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>管理员资料 - 聚合支付平台</title>
    <link rel="stylesheet" href="/static/style.css">
    <style>
        .profile-card {
            max-width: 420px;
            margin: 48px auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 8px 36px rgba(44,104,255,.10);
            padding: 0;
            overflow: hidden;
        }
        .card-header {
            padding: 22px 28px 8px 28px;
            background: linear-gradient(90deg, #3183f4 0%, #53b8f3 100%);
            color: #fff;
            border-radius: 16px 16px 0 0;
            font-size: 20px;
            font-weight: 600;
        }
        form {
            padding: 28px;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            font-size: 15px;
            color: #333;
            margin-bottom: 6px;
            display: block;
        }
        .form-group input {
            width: 100%;
            padding: 8px;
            font-size: 15px;
            border: 1px solid #dde3ec;
            border-radius: 7px;
            box-sizing: border-box;
        }
        .button {
            width: 100%;
            background: #3183f4;
            color: #fff;
            border: none;
            border-radius: 7px;
            padding: 10px 0;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background .2s;
        }
        .button:hover {
            background: #256dd7;
        }
        .alert {
            margin-bottom: 18px;
            color: #d33;
            text-align: center;
            font-size: 15px;
        }
        .success {
            color: #2cb26c;
        }
    </style>
</head>
<body>
<div class="topnav">
    <div class="logo"><a href="/admin/index.php" style="color:#fff;text-decoration:none;">聚合支付平台</a></div>
</div>
<div class="profile-card">
    <div class="card-header">管理员资料</div>
    <?php if ($msg): ?>
        <div class="alert <?= $success ? 'success' : '' ?>"><?=admin_esc($msg)?></div>
    <?php endif; ?>
    <form method="post" autocomplete="off">
        <div class="form-group">
            <label>用户名（不可修改）</label>
            <input type="text" value="<?=admin_esc($admin['username'])?>" disabled>
        </div>
        <div class="form-group">
            <label>新密码（如需修改）</label>
            <input type="password" name="password" placeholder="留空则不修改">
        </div>
        <div class="form-group">
            <label>重复新密码</label>
            <input type="password" name="password2" placeholder="留空则不修改">
        </div>
        <button class="button" type="submit">保存修改</button>
    </form>
</div>
<?php require_once 'footer.php'; ?>
</body>
</html>