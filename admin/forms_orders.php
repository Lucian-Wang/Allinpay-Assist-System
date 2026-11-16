<?php
$page_title = '表单订单';
require_once 'header.php';
$db = get_admin_db();
$form_id = intval($_GET['form_id'] ?? 0);
$where = '';
$params = [];
$form_name = '';
$kw = trim($_GET['kw'] ?? '');

// 分页参数
$page = max(1, intval($_GET['page'] ?? 1));
$page_size = 10;
$offset = ($page - 1) * $page_size;

// 过滤表单
if ($form_id) {
    $stmt = $db->prepare("SELECT name FROM forms WHERE id=?");
    $stmt->execute([$form_id]);
    $finfo = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$finfo) exit('参数错误');
    $where = 'AND o.form_id=?';
    $params[] = $form_id;
    $form_name = $finfo['name'];
}

// 搜索功能
if ($kw !== '') {
    $where .= " AND (
        o.order_no LIKE ?
        OR o.remark LIKE ?
        OR f.name LIKE ?
        OR o.field_values LIKE ?
    )";
    $like_kw = "%{$kw}%";
    $params[] = $like_kw;
    $params[] = $like_kw;
    $params[] = $like_kw;
    $params[] = $like_kw;
}

// 统计总数
$sql_count = "SELECT COUNT(*) FROM form_orders o LEFT JOIN forms f ON o.form_id=f.id WHERE 1=1 $where";
$stmt = $db->prepare($sql_count);
$stmt->execute($params);
$total_count = $stmt->fetchColumn();

// 查询分页数据 —— 直接拼接LIMIT/OFFSET
$sql = "SELECT o.*, f.name as form_name FROM form_orders o LEFT JOIN forms f ON o.form_id=f.id WHERE 1=1 $where ORDER BY o.id DESC LIMIT $page_size OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$status_text = [
    'pending' => '等待支付',
    'paid' => '支付成功',
    'expired' => '已失效'
];

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
    $html .= " 第 $page / $total_pages 页 ";
    if ($page < $total_pages) {
        $html .= '<a href="'.$baseurl.'page='.($page+1).'" class="btn" style="margin-left:4px;">下一页 &gt;</a> ';
        $html .= '<a href="'.$baseurl.'page='.$total_pages.'" class="btn" style="margin-left:4px;">末页 &raquo;</a>';
    }
    $html .= '</div></div>';
    return $html;
}
$baseurl = 'forms_orders.php?';
if ($form_id) $baseurl .= 'form_id=' . intval($form_id) . '&';
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
    .order-actions {
        display: flex;
        flex-direction: row;
        gap: 12px;
        align-items: center;
        justify-content: flex-start;
        white-space: nowrap;
    }
    .order-actions .btn, .order-actions .btn-red {
        min-width: 60px;
        max-width: 100px;
        width: auto;
        margin: 0;
        padding: 5px 18px;
        box-sizing: border-box;
        text-align: center;
    }
</style>
<div class="content-card" style="max-width:900px;margin:48px auto;">
    <div class="card-header" style="margin-bottom:20px;">
        <div class="card-title">表单订单<?php if($form_id):?>（<?=admin_esc($form_name)?>）<?php endif;?></div>
        <div class="card-actions">
            <form method="get" action="forms_orders.php" style="display:inline-block;margin-right:14px;">
                <?php if ($form_id): ?>
                    <input type="hidden" name="form_id" value="<?=admin_esc($form_id)?>">
                <?php endif; ?>
                <input type="text" name="kw" value="<?=admin_esc($kw)?>" placeholder="订单号/姓名/备注" style="padding:6px 12px;width:180px;">
                <button type="submit" class="btn">搜索</button>
            </form>
            <a href="forms.php" class="btn">返回</a>
        </div>
    </div>
    <table class="table" style="width:100%;background:#fff;">
        <thead>
            <tr>
                <th style="width:70px;">订单号</th>
                <th>表单</th>
                <th>金额</th>
                <th>状态</th>
                <th>支付时间</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $o): ?>
            <tr>
                <td><?=admin_esc($o['order_no'])?></td>
                <td><?=admin_esc($o['form_name'])?></td>
                <td><?=number_format($o['amount'],2)?></td>
                <td><?=admin_esc($status_text[$o['status']] ?? $o['status'])?></td>
                <td><?=admin_esc($o['paid_at'])?></td>
            </tr>
        <?php endforeach; ?>
        <?php if(empty($orders)): ?>
            <tr><td colspan="5" style="text-align:center;color:#888;">暂无订单</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <?=render_pagination($page, $total_pages, $baseurl);?>
</div>
<?php require_once 'footer.php'; ?>