<?php
require_once '../header.php'; // 让 require_login() 可用
require_login();
$user = current_user();
$id = intval($_GET['id'] ?? 0);
if (!$id) exit('参数错误');
$db = get_db();
$stmt = $db->prepare("UPDATE commonpay_channels c LEFT JOIN merchants m ON c.merchant_id=m.id SET c.status=0 WHERE c.id=? AND m.user_id=?");
$stmt->bind_param('ii', $id, $user['id']);
$stmt->execute();
redirect('index.php');