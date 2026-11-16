<?php
require_once '../../header.php';
require_login();
$user = current_user();
$db = get_db();

$project_id = intval($_GET['project_id'] ?? 0);
if (!$project_id) exit('参数错误');

// 查询项目
$stmt = $db->prepare("SELECT * FROM bills_projects WHERE id=? AND user_id=?");
$stmt->bind_param('ii', $project_id, $user['id']);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$project) exit('账单项目不存在');

// 搜索功能处理
$search = trim($_GET['search'] ?? '');
$where = "i.project_id=? AND i.user_id=? AND i.status=1";
$params = [$project_id, $user['id']];
$types = "ii";

if ($search !== '') {
    $where .= " AND (i.customer_tag LIKE ? OR i.customer_name LIKE ? OR i.remark LIKE ?)";
    $search_like = '%' . $search . '%';
    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_like;
    $types .= "sss";
}

// 分页处理
$page = max(1, intval($_GET['page'] ?? 1));
$page_size = 20;

// 查询总数
$count_sql = "SELECT COUNT(*) FROM bills_items i WHERE $where";
$count_stmt = $db->prepare($count_sql);
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$count_stmt->bind_result($total_count);
$count_stmt->fetch();
$count_stmt->close();

$total_page = max(1, ceil($total_count / $page_size));
if ($page > $total_page) $page = $total_page;
$offset = ($page - 1) * $page_size;

$sql = "
SELECT i.*,
  (SELECT COUNT(1) FROM bills_orders o WHERE o.item_id=i.id) as total_orders,
  (SELECT COUNT(1) FROM bills_orders o WHERE o.item_id=i.id AND o.status='paid') as paid_orders
FROM bills_items i
WHERE $where
ORDER BY i.id DESC
LIMIT ? OFFSET ?
";
$stmt = $db->prepare($sql);
if (!$stmt) {
    die('SQL ERROR: ' . $db->error);
}
$bind_types = $types . "ii";
$bind_params = array_merge($params, [$page_size, $offset]);
$stmt->bind_param($bind_types, ...$bind_params);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 支付状态函数
function get_pay_status($item) {
    if (intval($item['paid_orders']) > 0) {
        if (intval($item['total_orders']) > intval($item['paid_orders'])) {
            return '<span style="color:#faad14;">部分支付</span>';
        } else {
            return '<span style="color:#52c41a;">已支付</span>';
        }
    } else {
        return '<span style="color:#888;">未支付</span>';
    }
}

// 支付时间显示函数
function get_paid_time($item) {
    // 支付时间字段为 paid_at，未支付显示为空或“-”
    if (!empty($item['paid_at']) && $item['pay_status'] == 1) {
        return esc($item['paid_at']);
    }
    return '-';
}

// 获取项目code
$project_code = esc($project['code']);
?>
<div class="content-card" style="max-width:1100px;margin:48px auto;">
    <div class="card-header" style="margin-bottom:20px;">
        <div class="card-title">账单明细（<?=esc($project['name'])?>）</div>
        <div class="card-actions">
            <a class="button" href="create.php?project_id=<?=$project_id?>">+ 新建明细</a>
            <a class="button" href="import.php?project_id=<?=$project_id?>">导入</a>
            <a class="button" href="export.php?project_id=<?=$project_id?>">导出</a>
            <a class="button" href="../index.php">返回项目</a>
        </div>
    </div>
    <form method="get" style="margin-bottom:18px;display:flex;gap:8px;align-items:center;">
        <input type="hidden" name="project_id" value="<?=htmlspecialchars($project_id)?>">
        <input type="text" name="search" value="<?=htmlspecialchars($search)?>" placeholder="搜索客户标识/姓名/备注" style="padding:8px 12px;border:1px solid #b3cdfa;border-radius:6px;width:220px;">
        <button type="submit" style="padding:8px 22px;border-radius:6px;background:#1677ff;color:#fff;border:none;">搜索</button>
        <?php if ($search !== ''): ?>
            <a href="index.php?project_id=<?=$project_id?>" style="margin-left:8px;color:#999;">清除</a>
        <?php endif; ?>
    </form>
    <table class="table" style="width:100%;background:#fff;">
        <thead>
            <tr>
                <th>ID</th>
                <th>客户标识</th>
                <th>客户姓名</th>
                <th>应收金额</th>
                <th>备注</th>
                <th>创建时间</th>
                <th>支付状态</th>
                <th>支付时间</th>
                <th style="width:140px;">操作</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?=esc($item['id'])?></td>
                <td><?=esc($item['customer_tag'])?></td>
                <td><?=esc($item['customer_name'])?></td>
                <td><?=number_format($item['amount'],2)?></td>
                <td><?=esc($item['remark'])?></td>
                <td><?=esc($item['created_at'])?></td>
                <td><?=get_pay_status($item)?></td>
                <td><?=get_paid_time($item)?></td>
                <td>
                    <a href="../orders.php?item_id=<?=$item['id']?>">订单</a>
                    <span style="color:#ccc;">|</span>
                    <?php if (intval($item['paid_orders']) === 0): ?>
                        <a href="edit.php?project_id=<?=$project_id?>&id=<?=$item['id']?>">编辑</a>
                    <?php else: ?>
                        <span style="color:#bbb;cursor:not-allowed;">编辑</span>
                    <?php endif; ?>
                    <span style="color:#ccc;">|</span>
                    <?php if (intval($item['paid_orders']) === 0): ?>
                        <a href="delete.php?project_id=<?=$project_id?>&id=<?=$item['id']?>" onclick="return confirm('确定要删除吗？');" style="color:#f56c6c;">删除</a>
                    <?php else: ?>
                        <span style="color:#999;">删除</span>
                    <?php endif; ?>
                    <span style="color:#ccc;">|</span>
                    <!-- 传递2个参数跳转按钮 -->
                    <a href="/bills/pay.php?code=<?=$project_code?>&item_id=<?=$item['id']?>" target="_blank" style="color:#1677ff;">支付页</a>
                    <span style="color:#ccc;">|</span>
                    <!-- 二维码按钮（弹窗/新窗口打开二维码页） -->
                    <a href="javascript:void(0);" onclick="showQr('<?=$project_code?>', '<?=$item['id']?>')" style="color:#16cfa8;">二维码</a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($items)): ?>
            <tr><td colspan="9" style="color:#888;text-align:center;">暂无数据</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <!-- 分页导航 -->
    <div style="margin:30px 0 0 0; text-align:center;">
        <?php if ($total_page > 1): ?>
            <div class="pagination" style="display:inline-block;">
                <?php if ($page > 1): ?>
                    <a href="?project_id=<?=$project_id?>&search=<?=urlencode($search)?>&page=1" style="padding:6px 12px;">首页</a>
                    <a href="?project_id=<?=$project_id?>&search=<?=urlencode($search)?>&page=<?=($page-1)?>" style="padding:6px 12px;">上一页</a>
                <?php endif; ?>
                <?php
                $start = max(1, $page - 2);
                $end = min($total_page, $page + 2);
                for ($i = $start; $i <= $end; $i++):
                ?>
                    <?php if ($i == $page): ?>
                        <span style="background:#1677ff;color:#fff;padding:6px 12px;border-radius:4px;"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?project_id=<?=$project_id?>&search=<?=urlencode($search)?>&page=<?=$i?>" style="padding:6px 12px;"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                <?php if ($page < $total_page): ?>
                    <a href="?project_id=<?=$project_id?>&search=<?=urlencode($search)?>&page=<?=($page+1)?>" style="padding:6px 12px;">下一页</a>
                    <a href="?project_id=<?=$project_id?>&search=<?=urlencode($search)?>&page=<?=$total_page?>" style="padding:6px 12px;">尾页</a>
                <?php endif; ?>
                <span style="margin-left:12px;color:#999;">共 <?=$total_count?> 条</span>
            </div>
        <?php endif; ?>
    </div>
</div>
<script>
function showQr(code, item_id) {
    var url = 'https://allinpay.zhuli.pro/bills/pay.php?code=' + encodeURIComponent(code) + '&item_id=' + encodeURIComponent(item_id);
    var qrcodeUrl = 'https://lib.zhuli.pro/qrcode.php?data=' + encodeURIComponent(url);
    // 弹窗方式
    var w = window.open('', '', 'width=400,height=460');
    w.document.write('<div style="padding:30px;text-align:center;font-family:sans-serif;">' +
        '<div style="font-size:18px;font-weight:bold;margin-bottom:14px;">扫码支付</div>' +
        '<img src="'+qrcodeUrl+'" width="260" height="260" style="border-radius:10px;border:1px solid #eee;"><br>' +
        '<div style="margin-top:12px;color:#555;">可用微信/支付宝扫码</div>' +
        '<div style="margin-top:22px;"><a href="'+url+'" target="_blank" style="font-size:15px;color:#1677ff;">直接打开支付页</a></div>' +
        '</div>');
}
</script>
<?php require_once '../../footer.php'; ?>