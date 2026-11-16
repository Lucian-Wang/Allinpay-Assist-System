<?php
require_once '../db.php';
require_once '../utils.php';
require_login();
$user = current_user();

$id = intval($_GET['id'] ?? 0);
if (!$id) exit('参数错误');

$db = get_db();

$stmt = $db->prepare("UPDATE forms SET status=0 WHERE id=? AND user_id=?");
if (!$stmt) {
    die('SQL ERROR: ' . $db->error);
}
$stmt->bind_param('ii', $id, $user['id']);
$stmt->execute();
$stmt->close();

redirect('index.php');
?>