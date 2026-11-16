<?php
require_once __DIR__.'/config.php';
require_once __DIR__.'/functions.php';
if (!function_exists('admin_esc')) {
    function admin_esc($str) {
        return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
if (session_status() == PHP_SESSION_NONE) session_start();
require_admin_login();
$admin = admin_current_user();
$current_path = $_SERVER['PHP_SELF'];
function admin_menu_active($path) {
    global $current_path;
    return strpos($current_path, $path)!==false ? 'active' : '';
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?=isset($page_title)?admin_esc($page_title):'聚合支付平台-管理后台'?></title>
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1">
    <link rel="stylesheet" href="/static/style.css">
    <style>
        body {
            background: #f6f9fc;
        }
        .topnav {
            display: flex;
            align-items: center;
            background: linear-gradient(90deg, #3183f4 0%, #53b8f3 100%);
            color: #fff;
            padding: 0 38px;
            height: 58px;
            font-size: 16px;
            box-shadow: 0 2px 8px rgba(44,104,255,.05);
            position: sticky;
            top: 0; z-index: 100;
        }
        .topnav .logo {
            font-size: 21px;
            font-weight: bold;
            margin-right: 48px;
        }
        .topnav .logo a {
            color: #fff;
            text-decoration: none;
        }
        .topnav .menu {
            flex: 1;
        }
        .topnav .menu a {
            color: #fff;
            text-decoration: none;
            margin-right: 26px;
            padding: 7px 10px;
            border-radius: 6px;
            transition: background .15s;
            font-size: 16px;
        }
        .topnav .menu a.active,
        .topnav .menu a:hover {
            background: rgba(255,255,255,0.13);
        }
        .topnav .user {
            margin-left: auto;
            color: #fff;
            font-size: 15px;
        }
        .topnav .user a {
            color: #ffd4d4;
            margin-left: 12px;
            text-decoration: underline;
            font-size: 15px;
        }
        .main-content {
            width: 100%;
            max-width: 1200px;
            margin: 32px auto 0 auto;
            background: none;
            min-height: 520px;
            padding: 0;
        }
        @media (max-width: 1200px) {
            .main-content {max-width: 98vw;}
        }
        @media (max-width: 920px) {
            .main-content {padding: 0;}
            .topnav {flex-direction: column; height: auto; align-items: flex-start; gap: 10px;}
            .topnav .logo {margin: 12px 0;}
            .topnav .user {margin-left: 0; margin-bottom: 12px;}
        }
        @media (max-width: 600px) {
            .main-content {padding: 0;}
        }
    </style>
</head>
<body>
<div class="topnav">
    <div class="logo">
        <a href="/admin/index.php">聚合支付平台</a>
    </div>
    <div class="menu">
        <a href="/admin/index.php" class="<?=admin_menu_active('/admin/index.php')?>">后台首页</a>
        <a href="/admin/admins.php" class="<?=admin_menu_active('/admin/admins.php')?>">管理员管理</a>
        <a href="/admin/users.php" class="<?=admin_menu_active('/admin/users.php')?>">用户管理</a>
        <a href="/admin/merchants.php" class="<?=admin_menu_active('/admin/merchants.php')?>">商户管理</a>
        <a href="/admin/codes.php" class="<?=admin_menu_active('/admin/codes.php')?>">收款码管理</a>
        <a href="/admin/forms.php" class="<?=admin_menu_active('/admin/forms.php')?>">表单管理</a>
        <a href="/admin/bills.php" class="<?=admin_menu_active('/admin/bills.php')?>">账单项目管理</a>
        <a href="/admin/commonpay.php" class="<?=admin_menu_active('/admin/commonpay.php')?>">支付通道管理</a>
    </div>
    <div class="user">
        <?=admin_esc($admin['username'])?> | <a href="/admin/logout.php">退出</a>
    </div>
</div>
<div class="main-content">