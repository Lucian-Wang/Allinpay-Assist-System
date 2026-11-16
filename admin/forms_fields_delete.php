<?php
require_once 'header.php';
$db = get_admin_db();
$id = intval($_GET['id'] ?? 0);
$form_id = intval($_GET['form_id'] ?? 0);
if (!$id || !$form_id) exit('参数错误');

$stmt = $db->prepare("DELETE FROM form_fields WHERE id=?");
$stmt->execute([$id]);
header("Location: forms_fields.php?form_id=$form_id");
exit;