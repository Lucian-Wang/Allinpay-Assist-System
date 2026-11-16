<?php
$page_title = '账单项目管理';
require_once 'header.php';
$db = get_admin_db();

// 分页参数
$page = max(1, intval($_GET['page'] ?? 1));
$page_size = 20;

// 支持状态筛选
$status = isset($_GET['status']) ? intval($_GET['status']) : 1; // 1=正常,0=禁用,-1=全部

// 拼接where条件
$where = '';
$where_arr = [];
$params = [];
if ($status === 0 || $status === 1) {
    $where_arr[] = 'b.status=?';
    $params[] = $status;
}
$where_sql = $where_arr ? 'WHERE ' . implode(' AND ', $where_arr) : '';

// 计算总条数
$count_sql = "SELECT COUNT(*) FROM bills_projects b $where_sql";
$count_stmt = $db->prepare($count_sql);
if ($where_arr) {
    $count_stmt->execute($params);
} else {
    $count_stmt->execute();
}
$total_count = $count_stmt->fetchColumn();
$count_stmt->closeCursor();

$total_page = max(1, ceil($total_count / $page_size));
if ($page > $total_page) $page = $total_page;
$offset = ($page - 1) * $page_size;

// 查询当前页数据
$list_sql = "SELECT b.*, u.username as user_name, m.name as merchant_name 
             FROM bills_projects b 
             LEFT JOIN users u ON b.user_id=u.id 
             LEFT JOIN merchants m ON b.merchant_id=m.id 
             $where_sql 
             ORDER BY b.id DESC 
             LIMIT $page_size OFFSET $offset";
$list_stmt = $db->prepare($list_sql);
if ($where_arr) {
    $list_stmt->execute($params);
} else {
    $list_stmt->execute();
}
$projects = $list_stmt->fetchAll(PDO::FETCH_ASSOC);
$list_stmt->closeCursor();
?>
<div class="content-card" style="max-width:1100px;margin:48px auto;">
    <div class="card-header" style="margin-bottom:20px;">
        <div class="card-title">账单项目管理</div>
        <div class="card-actions">
            <a class="button" href="bills_add.php">+ 新建项目</a>
            <a href="bills.php?status=1" class="button" style="<?=$status===1?'background:#409eff;color:#fff;':''?>">正常</a>
            <a href="bills.php?status=0" class="button" style="<?=$status===0?'background:#409eff;color:#fff;':''?>">禁用</a>
            <a href="bills.php?status=-1" class="button" style="<?=$status===-1?'background:#409eff;color:#fff;':''?>">全部</a>
        </div>
    </div>
    <div style="overflow-x:auto;">
        <table class="table" style="width:100%;background:#fff;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>归属用户</th>
                    <th>项目名称</th>
                    <th>描述</th>
                    <th>商户</th>
                    <th>短码</th>
                    <th>状态</th>
                    <th>创建时间</th>
                    <th style="width:260px;">操作</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($projects as $p): ?>
                <tr>
                    <td><?=admin_esc($p['id'])?></td>
                    <td><?=admin_esc($p['user_name'])?></td>
                    <td><?=admin_esc($p['name'])?></td>
                    <td><?=admin_esc($p['desc'])?></td>
                    <td><?=admin_esc($p['merchant_name'])?></td>
                    <td><?=admin_esc($p['code'])?></td>
                    <td>
                        <?php if ($p['status']==1): ?><span style="color:green;">正常</span>
                        <?php else: ?><span style="color:#d33;">禁用</span>
                        <?php endif; ?>
                    </td>
                    <td><?=admin_esc($p['created_at'])?></td>
                    <td>
                        <a href="bills_view.php?id=<?=$p['id']?>">详情</a>
                        <span style="color:#ccc;">|</span>
                        <a href="bills_edit.php?id=<?=$p['id']?>">编辑</a>
                        <span style="color:#ccc;">|</span>
                        <a href="bills_items.php?project_id=<?=$p['id']?>">明细</a>
                        <span style="color:#ccc;">|</span>
                        <a href="bills_orders.php?project_id=<?=$p['id']?>">订单</a>
                        <span style="color:#ccc;">|</span>
                        <?php if ($p['status']==1): ?>
                            <a href="bills_disable.php?id=<?=$p['id']?>" onclick="return confirm('确定禁用？')">禁用</a>
                        <?php else: ?>
                            <a href="bills_enable.php?id=<?=$p['id']?>" onclick="return confirm('确定启用？')">启用</a>
                        <?php endif; ?>
                        <span style="color:#ccc;">|</span>
                        <a href="bills_delete.php?id=<?=$p['id']?>" onclick="return confirm('确定要永久删除？此操作不可恢复！')" style="color:#d33;">删除</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($projects)): ?>
                <tr><td colspan="9" style="color:#888;text-align:center;">暂无数据</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <!-- 分页导航 -->
    <div style="margin:30px 0 0 0; text-align:center;">
        <?php if ($total_page > 1): ?>
            <div class="pagination" style="display:inline-block;">
                <?php if ($page > 1): ?>
                    <a href="?status=<?=$status?>&page=1" style="padding:6px 12px;">首页</a>
                    <a href="?status=<?=$status?>&page=<?=($page-1)?>" style="padding:6px 12px;">上一页</a>
                <?php endif; ?>
                <?php
                $start = max(1, $page - 2);
                $end = min($total_page, $page + 2);
                for ($i = $start; $i <= $end; $i++):
                ?>
                    <?php if ($i == $page): ?>
                        <span style="background:#1677ff;color:#fff;padding:6px 12px;border-radius:4px;"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?status=<?=$status?>&page=<?=$i?>" style="padding:6px 12px;"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                <?php if ($page < $total_page): ?>
                    <a href="?status=<?=$status?>&page=<?=($page+1)?>" style="padding:6px 12px;">下一页</a>
                    <a href="?status=<?=$status?>&page=<?=$total_page?>" style="padding:6px 12px;">尾页</a>
                <?php endif; ?>
                <span style="margin-left:12px;color:#999;">共 <?=$total_count?> 条</span>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once 'footer.php'; ?>