<?php
$page_title = '支付通道管理';
require_once '../header.php';
require_login();
$user = current_user();
$db = get_db();
// 只显示自己商户下的通道
$stmt = $db->prepare("SELECT c.*, m.name AS merchant_name 
    FROM commonpay_channels c 
    LEFT JOIN merchants m ON c.merchant_id=m.id 
    WHERE m.user_id=? 
    ORDER BY c.id DESC");
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$channels = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<div class="content-card" style="max-width:900px;margin:48px auto;">
    <div class="card-header" style="margin-bottom:20px;">
        <div class="card-title">支付通道管理</div>
        <div class="card-actions">
            <a class="button" href="channel_create.php">+ 新建通道</a>
            <a class="button" href="help.php">使用说明</a>
            <a class="button" href="apihelp.php">API</a>
            <a class="button" href="gen_link.php">支付链接拼装生成器</a>
        </div>
    </div>
    <table class="table" style="width:100%;background:#fff;">
        <thead>
            <tr>
                <th>ID</th>
                <th>通道标识</th>
                <th>通道名称</th>
                <th>归属商户</th>
                <th>状态</th>
                <th>创建时间</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($channels as $c): ?>
            <tr>
                <td><?=esc($c['id'])?></td>
                <td><?=esc($c['channel'])?></td>
                <td><?=esc($c['name'])?></td>
                <td><?=esc($c['merchant_name'])?></td>
                <td><?=esc($c['status']==1?'启用':'禁用')?></td>
                <td><?=esc($c['created_at'])?></td>
                <td>
                    <a href="channel_edit.php?id=<?=$c['id']?>">编辑</a>
                    <span style="color:#ccc;">|</span>
                    <?php if($c['status']==1): ?>
                        <a href="channel_disable.php?id=<?=$c['id']?>" style="color:#f56c6c;" onclick="return confirm('确定要禁用该通道吗？');">禁用</a>
                    <?php else: ?>
                        <a href="channel_enable.php?id=<?=$c['id']?>" onclick="return confirm('确定要启用该通道吗？');">启用</a>
                    <?php endif;?>
                    <span style="color:#ccc;">|</span>
                    <a href="orders.php?channel_id=<?=$c['id']?>">订单</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once '../footer.php'; ?>