<?php
require_once 'header.php';
$db = get_admin_db();
$id = intval($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) die('用户不存在');
?>
<div class="content-card" style="max-width:420px;margin:48px auto;">
    <div class="card-header">
        <div class="card-title">用户详情</div>
        <div class="card-actions">
            <a href="users.php">返回</a>
            <a href="users_edit.php?id=<?=$user['id']?>">编辑</a>
        </div>
    </div>
    <div style="padding:30px;">
        <div class="form-group" style="margin-bottom:14px;">ID：<?=admin_esc($user['id'])?></div>
        <div class="form-group" style="margin-bottom:14px;">用户名：<?=admin_esc($user['username'])?></div>
        <div class="form-group" style="margin-bottom:14px;">邮箱：<?=admin_esc($user['email'])?></div>
        <div class="form-group" style="margin-bottom:14px;">创建时间：<?=admin_esc($user['created_at'])?></div>
        <div class="form-group" style="margin-bottom:14px;">更新时间：<?=admin_esc($user['updated_at'])?></div>
    </div>
</div>
<?php require_once 'footer.php'; ?>