<?php
$page_title = '账单项目订单';
require_once 'header.php';
$db = get_admin_db();

$project_id = intval($_GET['project_id'] ?? 0);
if (!$project_id) exit('参数错误');

// 查询项目
$stmt = $db->prepare("SELECT b.*, u.username as user_name FROM bills_projects b LEFT JOIN users u ON b.user_id=u.id WHERE b.id=?");
$stmt->execute([$project_id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$project) exit('项目不存在');

// 分页
$page = max(1, intval($_GET['page'] ?? 1));
$page_size = 20;
$offset = ($page - 1) * $page_size;

// 查询总数
$stmt = $db->prepare("SELECT COUNT(*) FROM bills_orders WHERE project_id=?");
$stmt->execute([$project_id]);
$total_count = $stmt->fetchColumn();
$total_page = max(1, ceil($total_count / $page_size));

// 查询订单列表
$stmt = $db->prepare("SELECT * FROM bills_orders WHERE project_id=? ORDER BY id DESC LIMIT $page_size OFFSET $offset");
$stmt->execute([$project_id]);
$list = $stmt->fetchAll(PDO::FETCH_ASSOC);

function order_status_text($s) {
    if ($s === 'paid') return '<span style="color:#52c41a;">已支付</span>';
    if ($s === 'fail') return '<span style="color:#f56c6c;">支付失败</span>';
    if ($s === 'closed') return '<span style="color:#888;">已关闭</span>';
    return '<span style="color:#faad14;">未支付</span>';
}
?>
<div class="content-card" style="max-width:1000px;margin:48px auto;">
    <div class="card-header" style="margin-bottom:20px;">
        <div class="card-title">账单订单（<?=admin_esc($project['name'])?>）</div>
        <div class="card-actions"><a href="bills.php">返回项目</a></div>
    </div>
    <table class="table" style="width:100%;background:#fff;">
        <thead>
            <tr>
                <th>ID</th>
                <th>订单号</th>
                <th>客户标识</th>
                <th>客户姓名</th>
                <th>金额</th>
                <th>支付状态</th>
                <th>创建时间</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($list as $o): ?>
            <tr>
                <td><?=admin_esc($o['id'])?></td>
                <td><?=admin_esc($o['order_no'])?></td>
                <td><?=admin_esc($o['customer_tag'])?></td>
                <td><?=admin_esc($o['customer_name'])?></td>
                <td><?=number_format($o['amount'],2)?></td>
                <td><?=order_status_text($o['status'])?></td>
                <td><?=admin_esc($o['created_at'])?></td>
            </tr>
        <?php endforeach; ?>
        <?php if(empty($list)): ?>
            <tr><td colspan="7" style="text-align:center;color:#888;">暂无订单</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <!-- 分页导航 -->
    <?php if ($total_page > 1): ?>
    <div style="margin:30px 0 0 0; text-align:center;">
        <div class="pagination" style="display:inline-block;">
            <?php if ($page > 1): ?>
                <a href="?project_id=<?=$project_id?>&page=1" style="padding:6px 12px;">首页</a>
                <a href="?project_id=<?=$project_id?>&page=<?=($page-1)?>" style="padding:6px 12px;">上一页</a>
            <?php endif; ?>
            <?php
            $start = max(1, $page - 2);
            $end = min($total_page, $page + 2);
            for ($i = $start; $i <= $end; $i++):
            ?>
                <?php if ($i == $page): ?>
                    <span style="background:#1677ff;color:#fff;padding:6px 12px;border-radius:4px;"><?= $i ?></span>
                <?php else: ?>
                    <a href="?project_id=<?=$project_id?>&page=<?=$i?>" style="padding:6px 12px;"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($page < $total_page): ?>
                <a href="?project_id=<?=$project_id?>&page=<?=($page+1)?>" style="padding:6px 12px;">下一页</a>
                <a href="?project_id=<?=$project_id?>&page=<?=$total_page?>" style="padding:6px 12px;">尾页</a>
            <?php endif; ?>
            <span style="margin-left:12px;color:#999;">共 <?=$total_count?> 条</span>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php require_once 'footer.php'; ?>