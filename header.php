<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/utils.php';
$user = current_user();
$current_path = $_SERVER['PHP_SELF'];
function menu_active($path) {
    global $current_path;
    return strpos($current_path, $path)!==false ? 'active' : '';
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?=isset($page_title)?$page_title:'聚合支付平台'?></title>
    <link rel="stylesheet" href="/static/style.css">
</head>
<body>
<div class="topnav">
    <div class="logo"><a href="/" style="color:#fff;text-decoration:none;">聚合支付平台</a></div>
    <div class="menu">
        
        <a href="/users/index.php" class="<?=menu_active('/users/index.php')?>">用户中心</a>
        <a href="/codes/index.php" class="<?=menu_active('/codes/index.php')?>">收款码</a>
        <a href="/forms/index.php" class="<?=menu_active('/forms/index.php')?>">表单收款</a>
        <a href="/bills/index.php" class="<?=menu_active('/bills/index.php')?>">账单收款</a>
        <a href="/commonpay/index.php" class="<?=menu_active('/commonpay/index.php')?>">支付通道</a>
        <a href="/merchants/index.php" class="<?=menu_active('/merchants/index.php')?>">我的商户</a>
    </div>
    <div class="user">
        <?php if ($user): ?>
            <?=esc($user['username'])?> | <a href="/users/logout.php">退出</a>
        <?php else: ?>
            <a href="/users/login.php">登录</a> | <a href="/users/register.php">注册</a>
        <?php endif; ?>
    </div>
</div>
<div class="main-content">