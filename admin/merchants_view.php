<?php
require_once 'header.php';
$db = get_admin_db();
$id = intval($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT m.*, u.username as user_name FROM merchants m LEFT JOIN users u ON m.user_id=u.id WHERE m.id=?");
$stmt->execute([$id]);
$merchant = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$merchant) die('商户不存在');
?>
<h2>商户详情</h2>
<div class="content-card" style="max-width:540px;margin:0 auto;">
    <table class="table" style="margin-bottom:0;">
        <tr><th style="width:120px;">ID</th><td><?=admin_esc($merchant['id'])?></td></tr>
        <tr><th>归属用户</th><td><?=admin_esc($merchant['user_name'])?> (<?=admin_esc($merchant['user_id'])?>)</td></tr>
        <tr><th>商户名称</th><td><?=admin_esc($merchant['name'])?></td></tr>
        <tr><th>支付通道</th><td><?=admin_esc($merchant['pay_type'])?></td></tr>
        <tr><th>AppID</th><td><?=admin_esc($merchant['appid'])?></td></tr>
        <tr><th>CUSID</th><td><?=admin_esc($merchant['cusid'])?></td></tr>
        <tr>
            <th>商户私钥</th>
            <td>
                <?php if (!empty($merchant['priKey'])): ?>
                    <span style="color: green;">已设置</span>
                <?php else: ?>
                    <span style="color: red;">未设置</span>
                <?php endif; ?>
            </td>
        </tr>
        <tr><th>创建时间</th><td><?=admin_esc($merchant['created_at'])?></td></tr>
        <tr><th>更新时间</th><td><?=admin_esc($merchant['updated_at'])?></td></tr>
    </table>
    <div style="margin-top:18px;">
        <a href="merchants.php" class="button">返回</a>
        <a href="merchants_edit.php?id=<?=intval($merchant['id'])?>" class="button">编辑</a>
        <a href="merchants_delete.php?id=<?=intval($merchant['id'])?>" class="button" onclick="return confirm('确定要删除？')" style="color:#d33;">删除</a>
    </div>
</div>
<?php require_once 'footer.php'; ?>