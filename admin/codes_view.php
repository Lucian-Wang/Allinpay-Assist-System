<?php
require_once 'header.php';
$db = get_admin_db();
$id = intval($_GET['id'] ?? 0);
$stmt = $db->prepare('SELECT c.*, u.username as user_name, m.name as merchant_name FROM codes c LEFT JOIN users u ON c.user_id=u.id LEFT JOIN merchants m ON c.merchant_id=m.id WHERE c.id=?');
$stmt->execute([$id]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$p) die('收款码不存在');
?>
<h2>收款码详情</h2>
<table class="table" style="max-width:600px;margin:auto;">
    <tr><th>ID</th><td><?=admin_esc($p['id'])?></td></tr>
    <tr><th>归属用户</th><td><?=admin_esc($p['user_name'])?></td></tr>
    <tr><th>卡台名称</th><td><?=admin_esc($p['name'])?></td></tr>
    <tr><th>商户</th><td><?=admin_esc($p['merchant_name'])?></td></tr>
    <tr><th>短码</th><td><?=admin_esc($p['code'])?></td></tr>
    <tr><th>状态</th><td><?= $p['status']==1 ? '<span style="color:green;">正常</span>' : '<span style="color:#d33;">禁用</span>' ?></td></tr>
    <tr><th>创建时间</th><td><?=admin_esc($p['created_at'])?></td></tr>
    <tr><th>更新时间</th><td><?=admin_esc($p['updated_at'])?></td></tr>
</table>
<div style="text-align:center;margin:18px;">
    <a href="codes.php">返回列表</a>
    <a href="codes_edit.php?id=<?=$p['id']?>">编辑</a>
    <a href="codes_delete.php?id=<?=$p['id']?>" onclick="return confirm('确定要永久删除？')">删除</a>
</div>
<?php require_once 'footer.php'; ?>