<?php
require_once '../../header.php';
require_login();
$user = current_user();
$db = get_db();

$id = intval($_GET['id'] ?? 0);
$project_id = intval($_GET['project_id'] ?? 0);

if (!$id || !$project_id) exit('参数错误');

// 查询明细并校验归属和支付状态
$stmt = $db->prepare("SELECT id, pay_status FROM bills_items WHERE id=? AND project_id=? AND user_id=?");
$stmt->bind_param('iii', $id, $project_id, $user['id']);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) exit('无权限或数据不存在');

// 仅未支付的明细允许删除
if ($item['pay_status'] != 0) {
    echo '<div style="color:#d33;padding:40px;text-align:center;">已支付的明细不允许删除！<br><br><a href="index.php?project_id='.$project_id.'">返回</a></div>';
    exit;
}

// 删除明细
$stmt = $db->prepare("DELETE FROM bills_items WHERE id=? AND user_id=?");
$stmt->bind_param('ii', $id, $user['id']);
$stmt->execute();
$stmt->close();

header('Location: index.php?project_id='.$project_id);
exit;
?>