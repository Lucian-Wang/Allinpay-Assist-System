<?php
$page_title = '账单订单';
require_once '../header.php';
require_login();
$user = current_user();
$db = get_db();

// 获取参数
$item_id = intval($_GET['item_id'] ?? 0);
$project_id = intval($_GET['project_id'] ?? 0);
$kw = trim($_GET['kw'] ?? '');

// 拼接where条件
$where = 'WHERE o.user_id=?';
$params = [$user['id']];
$types = 'i';

$title = '订单列表';

if ($item_id) {
    $where .= ' AND o.item_id=?';
    $params[] = $item_id;
    $types .= 'i';
    // 查询明细名
    $item_stmt = $db->prepare("SELECT * FROM bills_items WHERE id=? AND user_id=?");
    $item_stmt->bind_param('ii', $item_id, $user['id']);
    $item_stmt->execute();
    $item = $item_stmt->get_result()->fetch_assoc();
    $item_stmt->close();
    $title = '明细订单（' . esc($item['customer_tag'] ?? '') . ' ' . esc($item['customer_name'] ?? '') . '）';
} elseif ($project_id) {
    $where .= ' AND o.project_id=?';
    $params[] = $project_id;
    $types .= 'i';
    // 查询项目名
    $proj_stmt = $db->prepare("SELECT * FROM bills_projects WHERE id=? AND user_id=?");
    $proj_stmt->bind_param('ii', $project_id, $user['id']);
    $proj_stmt->execute();
    $project = $proj_stmt->get_result()->fetch_assoc();
    $proj_stmt->close();
    $title = '项目全部订单（' . esc($project['name'] ?? '') . '）';
}

// 支持搜索
if ($kw !== '') {
    $where .= " AND (
        o.order_no LIKE ?
        OR o.remark LIKE ?
        OR o.customer_tag LIKE ?
        OR o.customer_name LIKE ?
    )";
    $like_kw = "%{$kw}%";
    $params = array_merge($params, [$like_kw, $like_kw, $like_kw, $like_kw]);
    $types .= 'ssss';
}

// 分页
$page = max(1, intval($_GET['page'] ?? 1));
$page_size = 10;
$offset = ($page - 1) * $page_size;

// 统计总数
$sql_count = "SELECT COUNT(*) FROM bills_orders o $where";
$stmt = $db->prepare($sql_count);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$stmt->bind_result($total_count);
$stmt->fetch();
$stmt->close();

// 查询订单
$sql = "SELECT o.*, i.customer_tag, i.customer_name FROM bills_orders o 
        LEFT JOIN bills_items i ON o.item_id=i.id 
        $where
        ORDER BY o.id DESC 
        LIMIT ? OFFSET ?";
$params_query = array_merge($params, [$page_size, $offset]);
$stmt = $db->prepare($sql);
$stmt->bind_param($types . 'ii', ...$params_query);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 状态文本
function order_status_text($s) {
    if ($s === 'paid') return '<span style="color:#52c41a;">已支付</span>';
    if ($s === 'fail') return '<span style="color:#f56c6c;">支付失败</span>';
    if ($s === 'closed') return '<span style="color:#888;">已关闭</span>';
    return '<span style="color:#faad14;">未支付</span>';
}
$total_pages = max(1, ceil($total_count / $page_size));

// 生成分页导航
function render_pagination($page, $total_pages, $baseurl) {
    $html = '<div style="margin-top:20px;text-align:center;">';
    if ($total_pages <= 1) return '';
    $html .= '<div class="pagination">';
    if ($page > 1) {
        $html .= '<a href="'.$baseurl.'page=1" class="btn" style="margin-right:4px;">&laquo; 首页</a> ';
        $html .= '<a href="'.$baseurl.'page='.($page-1).'" class="btn" style="margin-right:4px;">&lt; 上一页</a> ';
    }
    // 输出中间页码
    $start = max(1, $page - 2);
    $end = min($total_pages, $page + 2);
    for ($i = $start; $i <= $end; $i++) {
        if ($i == $page) {
            $html .= '<span class="btn" style="background:#3183f4;color:#fff;margin:0 2px;">'.$i.'</span>';
        } else {
            $html .= '<a href="'.$baseurl.'page='.$i.'" class="btn" style="margin:0 2px;">'.$i.'</a>';
        }
    }
    if ($page < $total_pages) {
        $html .= '<a href="'.$baseurl.'page='.($page+1).'" class="btn" style="margin-left:4px;">下一页 &gt;</a> ';
        $html .= '<a href="'.$baseurl.'page='.$total_pages.'" class="btn" style="margin-left:4px;">末页 &raquo;</a>';
    }
    $html .= '<span style="margin-left:12px;color:#999;">共 '.$total_pages.' 页</span>';
    $html .= '</div></div>';
    return $html;
}
$baseurl = 'orders.php?';
if ($item_id) $baseurl .= 'item_id=' . intval($item_id) . '&';
if ($project_id) $baseurl .= 'project_id=' . intval($project_id) . '&';
if ($kw !== '') $baseurl .= 'kw=' . urlencode($kw) . '&';
?>
<style>
    .btn {
        display: inline-block;
        padding: 5px 18px;
        border-radius: 4px;
        border: 1px solid #3183f4;
        background: #fff;
        color: #3183f4;
        text-decoration: none;
        cursor: pointer;
        font-size: 15px;
        transition: background 0.2s, color 0.2s;
        min-width: 36px;
        text-align: center;
        box-sizing: border-box;
    }
    .btn:hover {
        background: #3183f4;
        color: #fff !important;
    }
    .card-actions {
        display: flex;
        justify-content: flex-end;
        gap: 16px;
        align-items: center;
    }
    @media (max-width: 600px) {
        .card-actions { flex-direction: column; gap: 8px; }
    }
</style>
<div class="content-card" style="max-width:1000px;margin:48px auto;">
    <div class="card-header" style="margin-bottom:20px;">
        <div class="card-title"><?=esc($title)?></div>
        <div class="card-actions">
            <form method="get" action="orders.php" style="display:inline-block;margin-right:14px;">
                <?php if ($item_id): ?>
                    <input type="hidden" name="item_id" value="<?=esc($item_id)?>">
                <?php endif; ?>
                <?php if ($project_id): ?>
                    <input type="hidden" name="project_id" value="<?=esc($project_id)?>">
                <?php endif; ?>
                <input type="text" name="kw" value="<?=esc($kw)?>" placeholder="订单号/备注/客户" style="padding:6px 12px;width:180px;">
                <button type="submit" class="btn">搜索</button>
            </form>
            <a href="index.php" class="btn">返回</a>
        </div>
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
        <?php foreach ($orders as $o): ?>
            <tr>
                <td><?=esc($o['id'])?></td>
                <td><?=esc($o['order_no'])?></td>
                <td><?=esc($o['customer_tag'])?></td>
                <td><?=esc($o['customer_name'])?></td>
                <td><?=number_format($o['amount'],2)?></td>
                <td><?=order_status_text($o['status'])?></td>
                <td><?=esc($o['created_at'])?></td>
            </tr>
        <?php endforeach; ?>
        <?php if(empty($orders)): ?>
            <tr><td colspan="7" style="text-align:center;color:#888;">暂无订单</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <?=render_pagination($page, $total_pages, $baseurl);?>
</div>
<?php require_once '../footer.php'; ?>