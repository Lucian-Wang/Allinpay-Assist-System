<?php
$page_title = '表单管理';
require_once 'header.php';
$db = get_admin_db();

// 分页
$page = max(1, intval($_GET['page'] ?? 1));
$page_size = 20;
$offset = ($page - 1) * $page_size;

// 总数
$count_sql = "SELECT COUNT(*) FROM forms";
$total_count = $db->query($count_sql)->fetchColumn();

// 列表
$sql = "SELECT f.*, u.username as user_name, m.name as merchant_name 
        FROM forms f 
        LEFT JOIN users u ON f.user_id=u.id 
        LEFT JOIN merchants m ON f.merchant_id=m.id 
        ORDER BY f.id DESC 
        LIMIT $page_size OFFSET $offset";
$list = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$total_page = max(1, ceil($total_count / $page_size));
?>
<div class="content-card" style="max-width:1100px;margin:48px auto;">
    <div class="card-header" style="margin-bottom:20px;">
        <div class="card-title">表单管理</div>
        <div class="card-actions">
            <a class="button" href="forms_add.php">+ 新建表单</a>
        </div>
    </div>
    <table class="table" style="width:100%;background:#fff;">
        <thead>
            <tr>
                <th>ID</th>
                <th>表单名称</th>
                <th>归属用户</th>
                <th>绑定商户</th>
                <th>短码</th>
                <th style="width:260px;">操作</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($list as $f): ?>
            <tr>
                <td><?=admin_esc($f['id'])?></td>
                <td><?=admin_esc($f['name'])?></td>
                <td><?=admin_esc($f['user_name'])?> (<?=admin_esc($f['user_id'])?>)</td>
                <td><?=admin_esc($f['merchant_name'])?></td>
                <td><?=admin_esc($f['code'])?></td>
                <td>
                    <a href="forms_edit.php?id=<?=$f['id']?>">编辑</a>
                    <span style="color:#ccc;">|</span>
                    <a href="forms_fields.php?form_id=<?=$f['id']?>">字段</a>
                    <span style="color:#ccc;">|</span>
                    <a href="forms_orders.php?form_id=<?=$f['id']?>">订单</a>
                    <span style="color:#ccc;">|</span>
                    <a href="forms_delete.php?id=<?=$f['id']?>" onclick="return confirm('确定要删除吗？');" style="color:#f56c6c;">删除</a>
                </td>
            </tr>
        <?php endforeach; ?>
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