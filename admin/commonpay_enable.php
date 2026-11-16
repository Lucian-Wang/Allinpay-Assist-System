<?php
require_once 'header.php';
$db = get_admin_db();
$id = intval($_GET['id'] ?? 0);
if (!$id) exit('参数错误');
$stmt = $db->prepare("UPDATE commonpay_channels SET status=1 WHERE id=?");
$stmt->execute([$id]);
header('Location: commonpay.php');
exit;