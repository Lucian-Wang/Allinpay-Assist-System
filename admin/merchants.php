<?php
$page_title = '商户管理';
require_once 'header.php';
$db = get_admin_db();

// 分页
$page = max(1, intval($_GET['page'] ?? 1));
$page_size = 20;
$offset = ($page - 1) * $page_size;

// 统计
$count_sql = "SELECT COUNT(*) FROM merchants";
$count = $db->query($count_sql)->fetchColumn();

// 列表
$stmt = $db->query("SELECT m.*, u.username AS user_name 
    FROM merchants m 
    LEFT JOIN users u ON m.user_id=u.id 
    ORDER BY m.id DESC 
    LIMIT $page_size OFFSET $offset");
$list = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_page = max(1, ceil($count / $page_size));
?>
<div class="content-card" style="max-width:1100px;margin:48px auto;">
    <div class="card-header">
        <div class="card-title">商户管理</div>
        <div class="card-actions">
            <a class="button" href="merchants_add.php">+ 新建商户</a>
        </div>
    </div>
    <div style="overflow-x:auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>名称</th>
                    <th>归属用户</th>
                    <th>支付通道</th>
                    <th>AppID</th>
                    <th>CUSID</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($list as $row): ?>
                <tr>
                    <td><?=admin_esc($row['id'])?></td>
                    <td><?=admin_esc($row['name'])?></td>
                    <td><?=admin_esc($row['user_name'])?> (<?=admin_esc($row['user_id'])?>)</td>
                    <td><?=admin_esc($row['pay_type'])?></td>
                    <td><?=admin_esc($row['appid'])?></td>
                    <td><?=admin_esc($row['cusid'])?></td>
                    <td class="actions">
                        <a href="merchants_view.php?id=<?=$row['id']?>">查看</a>
                        <a href="merchants_edit.php?id=<?=$row['id']?>">编辑</a>
                        <a href="merchants_delete.php?id=<?=$row['id']?>" onclick="return confirm('确定删除？')">删除</a>
                    </td>
                </tr>
            <?php endforeach;?>
            </tbody>
        </table>
    </div>
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
            <span style="margin-left:12px;color:#999;">共 <?=$count?> 条</span>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php require_once 'footer.php'; ?>