<?php
$page_title = '账单项目详情';
require_once 'header.php';
$db = get_admin_db();
$id = intval($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT b.*, u.username as user_name, m.name as merchant_name FROM bills_projects b LEFT JOIN users u ON b.user_id=u.id LEFT JOIN merchants m ON b.merchant_id=m.id WHERE b.id=?");
$stmt->execute([$id]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$p) exit('项目不存在');
?>
<div class="content-card" style="max-width:480px;margin:48px auto;">
    <div class="card-header">
        <div class="card-title">账单项目详情</div>
        <div class="card-actions"><a href="bills.php">返回</a></div>
    </div>
    <table class="table" style="margin-bottom:0;">
        <tr><th style="width:120px;">ID</th><td><?=admin_esc($p['id'])?></td></tr>
        <tr><th>归属用户</th><td><?=admin_esc($p['user_name'])?> (<?=admin_esc($p['user_id'])?>)</td></tr>
        <tr><th>项目名称</th><td><?=admin_esc($p['name'])?></td></tr>
        <tr><th>商户</th><td><?=admin_esc($p['merchant_name'])?> (<?=admin_esc($p['merchant_id'])?>)</td></tr>
        <tr><th>描述</th><td><?=admin_esc($p['desc'])?></td></tr>
        <tr><th>短码</th><td><?=admin_esc($p['code'])?></td></tr>
        <tr>
            <th>状态</th>
            <td>
                <?php if ($p['status']==1): ?><span style="color:green;">正常</span>
                <?php else: ?><span style="color:#d33;">禁用</span>
                <?php endif; ?>
            </td>
        </tr>
        <tr><th>创建时间</th><td><?=admin_esc($p['created_at'])?></td></tr>
        <tr><th>更新时间</th><td><?=admin_esc($p['updated_at'])?></td></tr>
    </table>
    <div style="margin-top:18px;">
        <a href="bills_edit.php?id=<?=intval($p['id'])?>" class="button">编辑</a>
        <?php if ($p['status']==1): ?>
            <a href="bills_disable.php?id=<?=$p['id']?>" class="button" onclick="return confirm('确定禁用？')">禁用</a>
        <?php else: ?>
            <a href="bills_enable.php?id=<?=$p['id']?>" class="button" onclick="return confirm('确定启用？')">启用</a>
        <?php endif; ?>
        <a href="bills_delete.php?id=<?=intval($p['id'])?>" class="button" onclick="return confirm('确定要永久删除？此操作不可恢复！')" style="color:#d33;">删除</a>
    </div>
</div>
<?php require_once 'footer.php'; ?>