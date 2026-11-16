<?php
require_once 'header.php';
$db = get_admin_db();
$id = intval($_GET['id'] ?? 0);
if ($id > 0) {
    $stmt = $db->prepare('UPDATE codes SET status=0 WHERE id=?');
    $stmt->execute([$id]);
}
header('Location: codes.php?status=1');
exit;