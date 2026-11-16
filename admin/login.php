<?php
require_once 'functions.php';
require_once __DIR__.'/../utils.php';
require_once __DIR__.'/config.php';

if (session_status() == PHP_SESSION_NONE) session_start();

if (admin_logged_in()) {
    header('Location: /admin/index.php');
    exit;
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $token = $_POST['csrf_token'] ?? '';
    if (!csrf_check($token)) {
        $msg = '表单超时，请刷新重试';
    } elseif (!$username || !$password) {
        $msg = '请填写完整信息';
    } else {
        $db = get_admin_db();
        $stmt = $db->prepare("SELECT * FROM admin_users WHERE username=?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']);
            $_SESSION['admin'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'] ?? ''
            ];
            header('Location: /admin/index.php');
            exit;
        } else {
            $msg = '用户名或密码错误';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>管理员登录 - 聚合支付平台</title>
    <link rel="stylesheet" href="/static/style.css">
    <style>
        .content-card {
            max-width: 420px;
            margin: 48px auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 8px 36px rgba(44,104,255,.10);
            padding: 0;
            overflow: hidden;
        }
        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 22px 28px 8px 28px;
            background: linear-gradient(90deg, #3183f4 0%, #53b8f3 100%);
            color: #fff;
            border-radius: 16px 16px 0 0;
        }
        .card-title {
            font-size: 20px;
            font-weight: 600;
            color: #fff;
        }
        .card-actions a {
            color: #fff;
            text-decoration: underline;
            font-size: 15px;
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
    </style>
</head>
<body>
<div class="topnav">
    <div class="logo"><a href="/admin/login.php" style="color:#fff;text-decoration:none;">聚合支付平台</a></div>
</div>
<div class="content-card">
    <div class="card-header">
        <div class="card-title">管理员登录</div>
        <div class="card-actions">
            <a href="/">返回首页</a>
        </div>
    </div>
    <?php if ($msg): ?>
        <div class="alert"><?=admin_esc($msg)?></div>
    <?php endif; ?>
    <form method="post" autocomplete="off">
        <div class="form-group">
            <label>用户名</label>
            <input type="text" name="username" required>
        </div>
        <div class="form-group">
            <label>密码</label>
            <input type="password" name="password" required>
        </div>
        <input type="hidden" name="csrf_token" value="<?=csrf_token()?>">
        <button class="button" type="submit">登录</button>
    </form>
</div>
<?php require_once 'footer.php'; ?>
</body>
</html>