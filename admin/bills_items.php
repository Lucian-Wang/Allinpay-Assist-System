<?php
$page_title = '账单明细管理';
require_once 'header.php';
$db = get_admin_db();

$project_id = intval($_GET['project_id'] ?? 0);
if (!$project_id) exit('参数错误');

$stmt = $db->prepare("SELECT b.*, u.username as user_name FROM bills_projects b LEFT JOIN users u ON b.user_id=u.id WHERE b.id=?");
$stmt->execute([$project_id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$project) exit('项目不存在');

// 明细列表
$stmt = $db->prepare("SELECT * FROM bills_items WHERE project_id=? ORDER BY id DESC");
$stmt->execute([$project_id]);
$list = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<div class="content-card" style="max-width:900px;margin:48px auto;">
    <div class="card-header">
        <div class="card-title">账单明细管理（<?=admin_esc($project['name'])?>）</div>
        <div class="card-actions">
            <a class="button" href="bills_items_add.php?project_id=<?=$project_id?>">+ 新建明细</a>
            <a class="button" href="bills.php">返回项目</a>
        </div>
    </div>
    <div style="overflow-x:auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>客户标识</th>
                    <th>客户姓名</th>
                    <th>金额</th>
                    <th>备注</th>
                    <th>支付状态</th>
                    <th>创建时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($list as $item): ?>
                <tr>
                    <td><?=admin_esc($item['id'])?></td>
                    <td><?=admin_esc($item['customer_tag'])?></td>
                    <td><?=admin_esc($item['customer_name'])?></td>
                    <td><?=number_format($item['amount'],2)?></td>
                    <td><?=admin_esc($item['remark'])?></td>
                    <td><?=admin_esc($item['status'])?></td>
                    <td><?=admin_esc($item['created_at'])?></td>
                    <td class="actions">
                        <a href="bills_items_edit.php?id=<?=$item['id']?>">编辑</a>
                        <a href="bills_items_delete.php?id=<?=$item['id']?>&project_id=<?=$project_id?>" onclick="return confirm('确定删除？')">删除</a>
                    </td>
                </tr>
            <?php endforeach;?>
            <?php if (empty($list)): ?>
                <tr><td colspan="8" style="color:#888;text-align:center;">暂无明细</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once 'footer.php'; ?>