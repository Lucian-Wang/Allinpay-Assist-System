<?php
require_once 'header.php';
$db = get_admin_db();
$id = intval($_GET['id'] ?? 0);
if ($id > 1) { // 超管不可删
    $stmt = $db->prepare("DELETE FROM admin_users WHERE id=?");
    $stmt->execute([$id]);
}
header('Location: admins.php');
exit;