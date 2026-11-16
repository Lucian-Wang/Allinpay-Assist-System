<?php
require_once 'header.php';
$db = get_admin_db();
$id = intval($_GET['id'] ?? 0);
if (!$id) exit('参数错误');

// 软删除，把 status 设为 0
$stmt = $db->prepare("UPDATE bills_projects SET status=0 WHERE id=?");
$stmt->execute([$id]);
header('Location: bills.php');
exit;