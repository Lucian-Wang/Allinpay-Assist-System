<?php
require_once 'header.php';
$db = get_admin_db();
$id = intval($_GET['id'] ?? 0);
$form_id = intval($_GET['form_id'] ?? 0);
$dir = $_GET['dir'] ?? 'up'; // up/down

if (!$id || !$form_id) exit('参数错误');

$stmt = $db->prepare("SELECT id, sort FROM form_fields WHERE id=? AND form_id=?");
$stmt->execute([$id, $form_id]);
$cur = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$cur) exit('字段不存在');

if ($dir == 'up') {
    $stmt = $db->prepare("SELECT id, sort FROM form_fields WHERE form_id=? AND sort < ? ORDER BY sort DESC LIMIT 1");
    $stmt->execute([$form_id, $cur['sort']]);
} else {
    $stmt = $db->prepare("SELECT id, sort FROM form_fields WHERE form_id=? AND sort > ? ORDER BY sort ASC LIMIT 1");
    $stmt->execute([$form_id, $cur['sort']]);
}
$swap = $stmt->fetch(PDO::FETCH_ASSOC);
if ($swap) {
    $db->prepare("UPDATE form_fields SET sort=? WHERE id=?")->execute([$swap['sort'], $cur['id']]);
    $db->prepare("UPDATE form_fields SET sort=? WHERE id=?")->execute([$cur['sort'], $swap['id']]);
}
header("Location: forms_fields.php?form_id=$form_id");
exit;