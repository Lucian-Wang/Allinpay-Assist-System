<?php
$page_title = '支付通道管理';
require_once 'header.php';
$db = get_admin_db();

// 分页参数
$page = max(1, intval($_GET['page'] ?? 1));
$page_size = 20;
$offset = ($page - 1) * $page_size;

// 统计总数
$count_sql = "SELECT COUNT(*) FROM commonpay_channels c LEFT JOIN merchants m ON c.merchant_id=m.id LEFT JOIN users u ON m.user_id=u.id";
$total_count = $db->query($count_sql)->fetchColumn();

// 查询列表
$sql = "SELECT c.*, m.name AS merchant_name, u.username AS user_name
    FROM commonpay_channels c
    LEFT JOIN merchants m ON c.merchant_id=m.id
    LEFT JOIN users u ON m.user_id=u.id
    ORDER BY c.id DESC
    LIMIT $page_size OFFSET $offset";
$list = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$total_page = max(1, ceil($total_count / $page_size));
?>
<div class="content-card" style="max-width:900px;margin:48px auto;">
    <div class="card-header" style="margin-bottom:20px;">
        <div class="card-title">支付通道管理</div>
        <div class="card-actions">
            <a class="button" href="commonpay_create.php">+ 新建通道</a>
        </div>
    </div>
    <table class="table" style="width:100%;background:#fff;">
        <thead>
            <tr>
                <th>ID</th>
                <th>通道标识</th>
                <th>通道名称</th>
                <th>归属商户</th>
                <th>归属用户</th>
                <th>状态</th>
                <th>创建时间</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($list as $c): ?>
            <tr>
                <td><?=admin_esc($c['id'])?></td>
                <td><?=admin_esc($c['channel'])?></td>
                <td><?=admin_esc($c['name'])?></td>
                <td><?=admin_esc($c['merchant_name'])?></td>
                <td><?=admin_esc($c['user_name'])?></td>
                <td><?=admin_esc($c['status']==1?'启用':'禁用')?></td>
                <td><?=admin_esc($c['created_at'])?></td>
                <td>
                    <a href="commonpay_edit.php?id=<?=$c['id']?>">编辑</a>
                    <span style="color:#ccc;">|</span>
                    <?php if($c['status']==1): ?>
                        <a href="commonpay_disable.php?id=<?=$c['id']?>" style="color:#f56c6c;" onclick="return confirm('确定要禁用该通道吗？');">禁用</a>
                    <?php else: ?>
                        <a href="commonpay_enable.php?id=<?=$c['id']?>" onclick="return confirm('确定要启用该通道吗？');">启用</a>
                    <?php endif;?>
                    <span style="color:#ccc;">|</span>
                    <a href="commonpay_orders.php?channel_id=<?=$c['id']?>">订单</a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($list)): ?>
            <tr><td colspan="8" style="color:#888;text-align:center;">暂无数据</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <!-- 分页导航 -->
    <?php if ($total_page > 1): ?>
    <div style="margin:30px 0 0 0; text-align:center;">
        <div class="pagination" style="display:inline-block;">
            <?php if ($page > 1): ?>
                <a href="?page=1" style="padding:6px 12px;">首页</a>
                <a href="?page=<?=($page-1)?>" style="padding:6px 12px;">上一页</a>
            <?php endif; ?>
            <?php
            $start = max(1, $page - 2);
            $end = min($total_page, $page + 2);
            for ($i = $start; $i <= $end; $i++):
            ?>
                <?php if ($i == $page): ?>
                    <span style="background:#1677ff;color:#fff;padding:6px 12px;border-radius:4px;"><?= $i ?></span>
                <?php else: ?>
                    <a href="?page=<?=$i?>" style="padding:6px 12px;"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($page < $total_page): ?>
                <a href="?page=<?=($page+1)?>" style="padding:6px 12px;">下一页</a>
                <a href="?page=<?=$total_page?>" style="padding:6px 12px;">尾页</a>
            <?php endif; ?>
            <span style="margin-left:12px;color:#999;">共 <?=$total_count?> 条</span>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php require_once 'footer.php'; ?>