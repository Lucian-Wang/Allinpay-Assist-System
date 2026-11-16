<?php
require_once '../header.php';
require_login();
$user = current_user();
$db = get_db();

$id = intval($_GET['id'] ?? 0);
$token = $_GET['csrf_token'] ?? '';

if (!$id) exit('参数错误');
if (!csrf_check($token)) exit('非法请求，请刷新重试');

// 检查归属
$stmt = $db->prepare("SELECT id FROM bills_projects WHERE id=? AND user_id=?");
$stmt->bind_param('ii', $id, $user['id']);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($row) {
    // 软删除，把 status 设为 0
    $stmt = $db->prepare("UPDATE bills_projects SET status=0 WHERE id=? AND user_id=?");
    $stmt->bind_param('ii', $id, $user['id']);
    $stmt->execute();
    $stmt->close();
}

header('Location: index.php');
exit;
?>