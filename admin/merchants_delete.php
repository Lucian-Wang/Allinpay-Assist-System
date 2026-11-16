<?php
require_once 'header.php';
$db = get_admin_db();
$id = intval($_GET['id'] ?? 0);
$stmt = $db->prepare("DELETE FROM merchants WHERE id=?");
$stmt->execute([$id]);
header('Location: merchants.php');
exit;