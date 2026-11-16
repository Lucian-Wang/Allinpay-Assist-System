<?php
require_once '../db.php';
require_login();
$user = current_user();
$id = intval($_GET['id'] ?? 0);
if (!$id) exit('参数错误');
$db = get_db();
$stmt = $db->prepare("UPDATE commonpay_channels c LEFT JOIN merchants m ON c.merchant_id=m.id SET c.status=1 WHERE c.id=? AND m.user_id=?");
$stmt->bind_param('ii', $id, $user['id']);
$stmt->execute();
redirect('index.php');