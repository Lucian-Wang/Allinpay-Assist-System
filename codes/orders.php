<?php
$page_title = '收款订单';
require_once '../header.php';
require_login();
$user = current_user();
$db = get_db();
$code = $_GET['code'] ?? '';
$where = '';
$params = [];
$param_types = '';
$code_name = '';
$kw = trim($_GET['kw'] ?? '');

// 批量删除1小时前未支付订单
if (isset($_GET['del_expired']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $db->prepare("DELETE FROM code_orders WHERE user_id=? AND status='pending' AND created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $deleted_rows = $stmt->affected_rows;
    $stmt->close();
    $redirect = 'orders.php?deleted_expired=' . intval($deleted_rows);
    if ($code) $redirect .= '&code=' . urlencode($code);
    if ($kw !== '') $redirect .= '&kw=' . urlencode($kw);
    header("Location: $redirect");
    exit;
}

// 删除单个订单操作（仅能删除自己的未支付订单）
if (isset($_GET['del']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $del_order_no = $_GET['del'];
    $stmt = $db->prepare("DELETE FROM code_orders WHERE order_no=? AND user_id=? AND status='pending'");
    $stmt->bind_param('si', $del_order_no, $user['id']);
    $stmt->execute();
    $stmt->close();
    $redirect = 'orders.php';
    if ($code) $redirect .= '?code=' . urlencode($code);
    if ($kw !== '') $redirect .= ($code ? '&' : '?') . 'kw=' . urlencode($kw);
    header("Location: $redirect");
    exit;
}

// 分页参数
$page = max(1, intval($_GET['page'] ?? 1));
$page_size = 10;
$offset = ($page - 1) * $page_size;

// 过滤卡台
if ($code) {
    $stmt = $db->prepare("SELECT id,name FROM codes WHERE code=? AND user_id=?");
    $stmt->bind_param('si', $code, $user['id']);
    $stmt->execute();
    $cinfo = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$cinfo) exit('参数错误');
    $where = 'AND o.code_id=?';
    $params[] = $cinfo['id'];
    $param_types = 'i';
    $code_name = $cinfo['name'];
}

// 搜索功能
if ($kw !== '') {
    $where .= " AND (
        o.order_no LIKE ?
        OR o.remark LIKE ?
        OR c.name LIKE ?
        OR o.payer_info LIKE ?
    )";
    $like_kw = "%{$kw}%";
    $params[] = $like_kw;
    $params[] = $like_kw;
    $params[] = $like_kw;
    $params[] = $like_kw;
    $param_types .= 'ssss';
}

// 统计总数
$sql_count = "SELECT COUNT(*) FROM code_orders o LEFT JOIN codes c ON o.code_id=c.id WHERE o.user_id=? $where";
$params_count = array_merge([$user['id']], $params);
$param_types_count = 'i' . $param_types;
$stmt = $db->prepare($sql_count);
$stmt->bind_param($param_types_count, ...$params_count);
$stmt->execute();
$stmt->bind_result($total_count);
$stmt->fetch();
$stmt->close();

// 查询分页数据
$sql = "SELECT o.*, c.name as code_name FROM code_orders o LEFT JOIN codes c ON o.code_id=c.id WHERE o.user_id=? $where ORDER BY o.id DESC LIMIT ? OFFSET ?";
$params_query = array_merge([$user['id']], $params, [$page_size, $offset]);
$param_types_query = 'i' . $param_types . 'ii';
$stmt = $db->prepare($sql);
$stmt->bind_param($param_types_query, ...$params_query);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$status_text = [
    'pending' => '等待支付',
    'paid' => '支付成功',
    'expired' => '已失效'
];

// 计算总页数
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
$baseurl = 'orders.php?';
if ($code) $baseurl .= 'code=' . urlencode($code) . '&';
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
        /* 移除block或100%宽度，保证只自适应内容 */
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
    @media (max-width: 600px) {
        .card-actions { flex-direction: column; gap: 8px; }
    }
    /* 操作列按钮横向排列且不换行 */
    .order-actions {
        display: flex;
        flex-direction: row;
        gap: 12px;
        align-items: center;
        justify-content: flex-start;
        white-space: nowrap; /* 防止换行！ */
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
        <div class="card-title">收款订单<?php if($code):?>（<?=esc($code_name)?>）<?php endif;?></div>
        <div class="card-actions">
            <form method="get" action="orders.php" style="display:inline-block;margin-right:14px;">
                <?php if ($code): ?>
                    <input type="hidden" name="code" value="<?=esc($code)?>">
                <?php endif; ?>
                <input type="text" name="kw" value="<?=esc($kw)?>" placeholder="订单号/备注/卡台/付款人" style="padding:6px 12px;width:180px;">
                <button type="submit" class="btn">搜索</button>
            </form>
            <form method="get" action="orders.php" style="display:inline;">
                <?php if ($code): ?>
                    <input type="hidden" name="code" value="<?=esc($code)?>">
                <?php endif; ?>
                <?php if ($kw !== ''): ?>
                    <input type="hidden" name="kw" value="<?=esc($kw)?>">
                <?php endif; ?>
                <button type="submit" name="del_expired" value="1"
                    class="btn btn-red"
                    onclick="return confirm('确定要删除所有1小时前的未支付订单吗？');"
                >一键清理1小时前未支付订单</button>
            </form>
            <a href="index.php" class="btn">返回</a>
        </div>
    </div>
    <?php if (isset($_GET['deleted_expired'])): ?>
        <div style="color:green;margin-bottom:10px;">成功删除 <?=intval($_GET['deleted_expired'])?> 条1小时前的未支付订单。</div>
    <?php endif; ?>
    <table class="table" style="width:100%;background:#fff;">
        <thead>
            <tr>
                <th style="width:70px;">订单号</th>
                <th>卡台</th>
                <th>金额</th>
                <th>状态</th>
                <th>支付时间</th>
                <th style="width:120px;">操作</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $o): ?>
            <tr>
                <td><?=esc($o['order_no'])?></td>
                <td><?=esc($o['code_name'])?></td>
                <td><?=number_format($o['amount'],2)?></td>
                <td><?=esc($status_text[$o['status']] ?? $o['status'])?></td>
                <td><?=esc($o['paid_at'])?></td>
                <td>
                    <div class="order-actions">
                        <a href="order_view.php?order_no=<?=esc($o['order_no'])?>" 
                            class="btn">详情</a>
                        <?php if($o['status'] === 'pending'): ?>
                            <a href="orders.php?<?=($code ? 'code='.urlencode($code).'&':'')?><?=($kw !== '' ? 'kw='.urlencode($kw).'&' : '')?>del=<?=esc($o['order_no'])?>"
                                class="btn btn-red"
                                onclick="return confirm('确定要删除该未支付订单吗？');"
                            >删除</a>
                        <?php endif;?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if(empty($orders)): ?>
            <tr><td colspan="6" style="text-align:center;color:#888;">暂无订单</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <?=render_pagination($page, $total_pages, $baseurl);?>
</div>
<?php require_once '../footer.php'; ?>