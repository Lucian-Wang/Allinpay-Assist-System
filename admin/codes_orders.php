<?php
require_once 'header.php';
$code = trim($_GET['code'] ?? '');
$kw = trim($_GET['kw'] ?? '');

// 1. 查收款码信息
$db = get_admin_db();
if (!$code) die('缺少收款码参数');
$stmt = $db->prepare("SELECT id, name FROM codes WHERE code=?");
$stmt->execute([$code]);
$code_info = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$code_info) die('收款码不存在');

// 2. 搜索和分页
$where = "WHERE o.code_id=?";
$params = [$code_info['id']];
if ($kw !== '') {
    $where .= " AND (o.order_no LIKE ? OR o.remark LIKE ? OR m.name LIKE ? OR o.payer_info LIKE ?)";
    $like_kw = "%{$kw}%";
    $params = array_merge($params, [$like_kw, $like_kw, $like_kw, $like_kw]);
}

// 统计总数
$sql_count = "SELECT COUNT(*) FROM code_orders o LEFT JOIN merchants m ON o.merchant_id=m.id $where";
$stmt = $db->prepare($sql_count);
$stmt->execute($params);
$total_count = $stmt->fetchColumn();

// 分页
$page_size = 10;
$page = max(1, intval($_GET['page'] ?? 1));
$total_pages = max(1, ceil($total_count / $page_size));
$offset = ($page - 1) * $page_size;

// 查订单数据
$sql = "SELECT o.*, u.username as user_name, m.name as merchant_name 
        FROM code_orders o 
        LEFT JOIN users u ON o.user_id=u.id 
        LEFT JOIN merchants m ON o.merchant_id=m.id 
        $where
        ORDER BY o.id DESC LIMIT $page_size OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$status_text = [
    'pending' => '等待支付',
    'paid' => '支付成功',
    'expired' => '已失效'
];

// 生成分页导航
function render_pagination($page, $total_pages, $baseurl) {
    if ($total_pages <= 1) return '';
    $html = '<div class="pagination">';
    if ($page > 1) {
        $html .= '<a href="'.$baseurl.'page=1" class="btn">&laquo; 首页</a> ';
        $html .= '<a href="'.$baseurl.'page='.($page-1).'" class="btn">&lt; 上一页</a> ';
    }
    $html .= " 第 $page / $total_pages 页 ";
    if ($page < $total_pages) {
        $html .= '<a href="'.$baseurl.'page='.($page+1).'" class="btn">下一页 &gt;</a> ';
        $html .= '<a href="'.$baseurl.'page='.$total_pages.'" class="btn">末页 &raquo;</a>';
    }
    $html .= '</div>';
    return $html;
}
$baseurl = "codes_orders.php?code=" . urlencode($code) . ($kw !== '' ? '&kw='.urlencode($kw).'&' : '&');
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
        min-width: 60px;
        text-align: center;
        box-sizing: border-box;
    }
    .btn-red {
        border-color: #d9534f !important;
        color: #d9534f !important;
    }
    .btn:hover {
        background: #3183f4;
        color: #fff !important;
    }
    .btn-red:hover {
        background: #d9534f;
        color: #fff !important;
    }
    .card-actions {
        display: flex;
        justify-content: flex-end;
        gap: 16px;
        align-items: center;
    }
    .order-actions {
        display: flex;
        flex-direction: row;
        gap: 10px;
        align-items: center;
    }
    .order-actions .btn {
        min-width: 60px;
        max-width: 120px;
        width: auto;
        margin: 0;
        padding: 5px 18px;
    }
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 7px;
        margin: 24px 0;
    }
    .content-card {
        max-width: 1000px;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 4px 32px #0001;
        margin: 48px auto;
        padding: 32px;
    }
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 22px;
    }
    .card-title {
        font-size: 1.5em;
        font-weight: 600;
        color: #222;
        letter-spacing: 1px;
    }
    table.code-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        background: #fff;
        border-radius: 10px;
        overflow: hidden;
    }
    .code-table thead th {
        background: #f6f8fa;
        font-weight: 550;
        color: #444;
        border-bottom: 2px solid #eaeaea;
        padding: 12px 8px;
    }
    .code-table tbody td {
        padding: 12px 8px;
        text-align: center;
    }
    .code-table tbody tr:nth-child(odd) { background: #fafbfc; }
    .code-table tbody tr:hover { background: #e6f7ff; transition: background 0.2s;}
    @media (max-width: 900px){
        .content-card {padding:10px;}
        .card-title {font-size:1.1rem;}
        .code-table th, .code-table td {padding:7px 2px;}
    }
</style>
<div class="content-card">
    <div class="card-header">
        <div class="card-title">
            收款码订单（<?=admin_esc($code_info['name'])?> / <?=admin_esc($code)?>）
        </div>
        <div class="card-actions">
            <form method="get" action="codes_orders.php" style="display:inline-block;margin-right:14px;">
                <input type="hidden" name="code" value="<?=admin_esc($code)?>">
                <input type="text" name="kw" value="<?=admin_esc($kw)?>" placeholder="订单号/备注/商户/付款人" style="padding:6px 12px;width:180px;">
                <button type="submit" class="btn">搜索</button>
            </form>
            <a href="codes.php" class="btn">返回收款码列表</a>
        </div>
    </div>
    <table class="code-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>订单号</th>
                <th>用户</th>
                <th>商户</th>
                <th>金额</th>
                <th>状态</th>
                <th>下单时间</th>
                <th>支付时间</th>
                <th style="width:100px;">操作</th>
            </tr>
        </thead>
        <tbody>
        <?php if(empty($orders)): ?>
            <tr><td colspan="9" style="text-align:center;color:#888;">暂无订单</td></tr>
        <?php else: foreach ($orders as $o): ?>
            <tr>
                <td><?=admin_esc($o['id'])?></td>
                <td><?=admin_esc($o['order_no'])?></td>
                <td><?=admin_esc($o['user_name'])?></td>
                <td><?=admin_esc($o['merchant_name'])?></td>
                <td><?=number_format($o['amount'],2)?></td>
                <td>
                    <?php if ($o['status']=='paid'): ?>
                        <span style="color:green;">支付成功</span>
                    <?php elseif ($o['status']=='pending'): ?>
                        <span style="color:#faad14;">等待支付</span>
                    <?php else: ?>
                        <span style="color:#aaa;"><?=admin_esc($status_text[$o['status']] ?? $o['status'])?></span>
                    <?php endif; ?>
                </td>
                <td><?=admin_esc($o['created_at'])?></td>
                <td><?=admin_esc($o['paid_at'])?></td>
                <td>
                    <div class="order-actions">
                        <a href="codes_order_view.php?order_no=<?=admin_esc($o['order_no'])?>" class="btn">详情</a>
                    </div>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    <?=render_pagination($page, $total_pages, $baseurl);?>
</div>
<?php require_once 'footer.php'; ?>