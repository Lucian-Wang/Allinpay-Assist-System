<?php
require_once 'header.php';
$db = get_admin_db();
$id = intval($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT * FROM admin_users WHERE id=?");
$stmt->execute([$id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$admin) die('管理员不存在');
?>
<div class="content-card" style="max-width:420px;margin:48px auto;">
    <div class="card-header">
        <div class="card-title">管理员详情</div>
        <div class="card-actions">
            <a href="admins.php">返回</a>
            <a href="admins_edit.php?id=<?=$admin['id']?>">编辑</a>
        </div>
    </div>
    <div style="padding:30px;">
        <div class="form-group" style="margin-bottom:14px;">ID：<?=admin_esc($admin['id'])?></div>
        <div class="form-group" style="margin-bottom:14px;">用户名：<?=admin_esc($admin['username'])?></div>
        <div class="form-group" style="margin-bottom:14px;">创建时间：<?=admin_esc($admin['created_at'])?></div>
    </div>
</div>
<?php require_once 'footer.php'; ?>