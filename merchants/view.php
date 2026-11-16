<?php
require_once '../db.php';
require_once '../utils.php';
require_login();
$user = current_user();
$id = intval($_GET['id'] ?? 0);
$db = get_db();
$stmt = $db->prepare("SELECT * FROM merchants WHERE id=? AND user_id=?");
$stmt->bind_param('ii', $id, $user['id']);
$stmt->execute();
$merchant = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$merchant) die('商户不存在');
$page_title = '商户详情';
require_once '../header.php';
?>
<div class="content-card" style="max-width:540px;margin:0 auto;">
    <div class="card-header">
        <div class="card-title">商户详情</div>
        <div class="card-actions">
            <a href="index.php">返回</a>
            <a href="edit.php?id=<?=intval($merchant['id'])?>">编辑</a>
            <a href="/users/logout.php">退出</a>
        </div>
    </div>
    <table class="table" style="margin-bottom:0;">
        <tr><th style="width:120px;">名称</th><td><?=esc($merchant['name'])?></td></tr>
        <tr><th>支付通道</th><td><?=esc($merchant['pay_type'])?></td></tr>
        <tr><th>AppID</th><td><?=esc($merchant['appid'])?></td></tr>
        <tr><th>CUSID</th><td><?=esc($merchant['cusid'])?></td></tr>
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
        <tr><th>创建时间</th><td><?=esc($merchant['created_at'])?></td></tr>
        <tr><th>更新时间</th><td><?=esc($merchant['updated_at'])?></td></tr>
    </table>
</div>
<?php require_once '../footer.php'; ?>