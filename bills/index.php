<?php
require_once '../header.php';
require_login();
$user = current_user();
$db = get_db();

// 分页参数
$page = max(1, intval($_GET['page'] ?? 1));
$page_size = 20;

// 计算总条数
$count_sql = "SELECT COUNT(*) FROM bills_projects WHERE user_id=? AND status=1";
$count_stmt = $db->prepare($count_sql);
if (!$count_stmt) {
    die('SQL ERROR: ' . $db->error);
}
$count_stmt->bind_param('i', $user['id']);
$count_stmt->execute();
$count_stmt->bind_result($total_count);
$count_stmt->fetch();
$count_stmt->close();

$total_page = max(1, ceil($total_count / $page_size));
if ($page > $total_page) $page = $total_page;
$offset = ($page - 1) * $page_size;

// 查询当前页数据
$stmt = $db->prepare("SELECT b.*, m.name as merchant_name FROM bills_projects b LEFT JOIN merchants m ON b.merchant_id=m.id WHERE b.user_id=? AND b.status=1 ORDER BY b.id DESC LIMIT ? OFFSET ?");
if (!$stmt) {
    die('SQL ERROR: ' . $db->error);
}
$stmt->bind_param('iii', $user['id'], $page_size, $offset);
$stmt->execute();
$projects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<div class="content-card" style="max-width:900px;margin:48px auto;">
    <div class="card-header" style="margin-bottom:20px;">
        <div class="card-title">账单收款</div>
        <div class="card-actions">
            <a class="button" href="create.php">+ 新建项目</a>
            <a class="button" href="orders.php">全部订单</a>
        </div>
    </div>
    <table class="table" style="width:100%;background:#fff;">
        <thead>
            <tr>
                <th>ID</th>
                <th>项目名称</th>
                <th>绑定商户</th>
                <th>短码</th>
                <th style="width:260px;">操作</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($projects as $p): ?>
            <tr>
                <td><?=esc($p['id'])?></td>
                <td><?=esc($p['name'])?></td>
                <td><?=esc($p['merchant_name'])?></td>
                <td>
                    <?php if (!empty($p['code'])): ?>
                        <a href="pay.php?code=<?=urlencode($p['code'])?>" target="_blank"><?=esc($p['code'])?></a>
                    <?php else: ?>
                        <span style="color:#999;">--</span>
                        <span style="color:#ccc;">|</span>
                        <a href="pay.php?project_id=<?=esc($p['id'])?>" target="_blank"><?=esc($p['code'])?></a>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="edit.php?id=<?=$p['id']?>">编辑</a>
                    <span style="color:#ccc;">|</span>
                    <a href="items/index.php?project_id=<?=$p['id']?>">明细</a>
                    <span style="color:#ccc;">|</span>
                    <a href="orders.php?project_id=<?=$p['id']?>">订单</a>
                    <span style="color:#ccc;">|</span>
                    <?php
                        $domain = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
                        $link = $domain . "/bills/pay.php?" . (!empty($p['code']) ? ("code=" . urlencode($p['code'])) : ("project_id=" . urlencode($p['id'])));
                        $qrcode_img = "/codes/qrcode.php?"
                            . "data=" . urlencode($link)
                            . "&id=" . urlencode(!empty($p['code']) ? $p['code'] : $p['id'])
                            . "&company=" . urlencode($p['name']);
                    ?>
                    <a href="<?=$qrcode_img?>" target="_blank">二维码</a>
                    <span style="color:#ccc;">|</span>
                   <a href="delete.php?id=<?=$p['id']?>&csrf_token=<?=urlencode($_SESSION['csrf_token'])?>"
   onclick="return confirm('确定要删除吗？');"
   style="color:#f56c6c;">删除</a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($projects)): ?>
            <tr><td colspan="5" style="color:#888;text-align:center;">暂无数据</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <!-- 分页导航 -->
    <div style="margin:30px 0 0 0; text-align:center;">
        <?php if ($total_page > 1): ?>
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
        <?php endif; ?>
    </div>
</div>
<?php require_once '../footer.php'; ?>