<?php
require_once 'header.php';
$db = get_admin_db();
$id = intval($_GET['id'] ?? 0);
$project_id = intval($_GET['project_id'] ?? 0);
if (!$id) exit('参数错误');

$stmt = $db->prepare("DELETE FROM bills_items WHERE id=?");
$stmt->execute([$id]);
header('Location: bills_items.php?project_id='.$project_id);
exit;