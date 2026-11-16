<?php
$page_title = '通道订单列表';
require_once 'header.php';
$db = get_admin_db();

// 获取参数
$channel_id = intval($_GET['channel_id'] ?? 0);
$kw = trim($_GET['kw'] ?? '');

// 构造where条件
$where = "WHERE 1";
$params = [];

// 如果有channel_id则限定通道
if ($channel_id) {
    $where .= " AND o.channel_id=?";
    $params[] = $channel_id;
}

// 搜索条件
if ($kw !== '') {
    $where .= " AND (o.pay_order_no LIKE ? OR o.subject LIKE ? OR o.user_id LIKE ? OR o.biz_order_no LIKE ?)";
    $like_kw = "%{$kw}%";
    $params = array_merge($params, [$like_kw, $like_kw, $like_kw, $like_kw]);
}

// 分页
$page = max(1, intval($_GET['page'] ?? 1));
$page_size = 20;
$offset = ($page - 1) * $page_size;

// 统计总数
$sql_count = "SELECT COUNT(*) FROM commonpay_orders o $where";
$stmt = $db->prepare($sql_count);
$stmt->execute($params);
$total_count = $stmt->fetchColumn();
$stmt = null;

// 查询订单
$sql = "SELECT o.*, m.name AS merchant_name, c.name AS channel_name FROM commonpay_orders o 
        LEFT JOIN merchants m ON o.merchant_id=m.id 
        LEFT JOIN commonpay_channels c ON o.channel_id=c.id
        $where
        ORDER BY o.id DESC 
        LIMIT $page_size OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt = null;

// 最小化显示
function escmini($s, $len=12) {
    $s = admin_esc($s ?? '');
    if (mb_strlen($s, 'utf-8') > $len) {
        return mb_substr($s, 0, $len, 'utf-8') . '...';
    }
    return $s;
}

// 分页导航
$total_pages = max(1, ceil($total_count / $page_size));
function render_pagination($page, $total_pages, $baseurl) {
    $html = '<div style="margin-top:20px;text-align:center;">';
    if ($total_pages <= 1) return '';
    $html .= '<div class="pagination">';
    if ($page > 1) {
        $html .= '<a href="'.$baseurl.'page=1" class="btn" style="margin-right:4px;">&laquo; 首页</a> ';
        $html .= '<a href="'.$baseurl.'page='.($page-1).'" class="btn" style="margin-right:4px;">&lt; 上一页</a> ';
    }
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
$baseurl = 'commonpay_orders.php?';
if ($channel_id) $baseurl .= 'channel_id=' . intval($channel_id) . '&';
if ($kw !== '') $baseurl .= 'kw=' . urlencode($kw) . '&';

// 获取通道下拉
$channels = $db->query("SELECT id,name FROM commonpay_channels ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
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
    .table th, .table td {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 110px;
    }
    .content-card {
        max-width: 100vw !important;
    }
    @media (max-width: 1150px) {
        .content-card {
            max-width: 98vw !important;
        }
        .table th, .table td {
            max-width: 60px;
        }
    }
    @media (max-width: 900px) {
        .table th, .table td {
            max-width: 40px;
            font-size: 12px;
        }
    }
    .table {
        table-layout: fixed;
        width: 100%;
    }
    .card-actions select {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background: #fff url("data:image/svg+xml;charset=UTF-8,<svg width='12' height='8' xmlns='http://www.w3.org/2000/svg'><path d='M1 1l5 5 5-5' stroke='%233183f4' stroke-width='2' fill='none' fill[...]
        border: 1px solid #e5e6eb;
        border-radius: 8px;
        padding: 6px 32px 6px 12px;
        font-size: 15px;
        outline: none;
        transition: border-color .2s;
        box-sizing: border-box;
        min-width: 120px;
        cursor: pointer;
        margin-right: 10px;
    }
    .card-actions select:focus {
        border-color: #3183f4;
        background-color: #f7fafd;
    }
    .card-actions select::-ms-expand {
        display: none;
    }
    .card-actions select::-webkit-scrollbar {
        display: none;
    }
    .content-card > div[style*='overflow-x:auto']::-webkit-scrollbar {
        height: 0 !important;
    }
    .content-card > div[style*='overflow-x:auto'] {
        scrollbar-width: none;
        -ms-overflow-style: none;
    }
    .content-card > div[style*='overflow-x:auto']::-webkit-scrollbar {
        display: none;
    }
</style>
<div class="content-card" style="max-width:900px;margin:48px auto;min-width:650px;">
    <div class="card-header" style="margin-bottom:20px;">
        <div class="card-title">
            通道订单列表
            <?php if ($channel_id): ?>
                （<?=escmini($orders[0]['channel_name'] ?? '')?>）
            <?php endif; ?>
        </div>
        <div class="card-actions">
            <form method="get" action="commonpay_orders.php" style="display:inline-block;margin-right:14px;">
                <select name="channel_id" onchange="this.form.submit()">
                    <option value="0">全部通道</option>
                    <?php foreach($channels as $c): ?>
                        <option value="<?=admin_esc($c['id'])?>" <?=($channel_id==$c['id'])?'selected':''?>><?=admin_esc($c['name'])?></option>
                    <?php endforeach;?>
                </select>
                <input type="text" name="kw" value="<?=admin_esc($kw)?>" placeholder="订单号/商品/用户/业务单号" style="padding:6px 12px;width:180px;">
                <button type="submit" class="btn">搜索</button>
            </form>
            <a href="commonpay.php" class="btn">返回通道管理</a>
        </div>
    </div>
    <div style="overflow-x:auto;">
    <table class="table" style="background:#fff;">
        <thead>
            <tr>
                <th style="width:44px;">ID</th>
                <th style="width:110px;">订单号</th>
                <th style="width:80px;">通道</th>
                <th style="width:80px;">商户</th>
                <th style="width:60px;">金额</th>
                <th style="width:90px;">商品</th>
                <th style="width:56px;">状态</th>
                <th style="width:110px;">通联交易号</th>
                <th style="width:90px;">支付时间</th>
                <th style="width:50px;">操作</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $o): ?>
            <tr>
                <td><?=admin_esc($o['id'])?></td>
                <td title="<?=admin_esc($o['pay_order_no'])?>"><?=escmini($o['pay_order_no'], 12)?></td>
                <td title="<?=admin_esc($o['channel_name'])?>"><?=escmini($o['channel_name'], 6)?></td>
                <td title="<?=admin_esc($o['merchant_name'])?>"><?=escmini($o['merchant_name'], 6)?></td>
                <td><?=isset($o['amount']) ? number_format($o['amount'],2) : ''?></td>
                <td title="<?=admin_esc($o['subject'])?>"><?=escmini($o['subject'], 7)?></td>
                <td><?=admin_esc($o['status'])?></td>
                <td title="<?=admin_esc($o['allinpay_trxid'])?>"><?=escmini($o['allinpay_trxid'], 10)?></td>
                <td><?=escmini($o['paid_at'], 10)?></td>
                <td><a href="commonpay_order_view.php?pay_order_no=<?=admin_esc($o['pay_order_no'])?>">详情</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if(empty($orders)): ?>
            <tr><td colspan="10" style="text-align:center;color:#888;">暂无订单</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
    <?=render_pagination($page, $total_pages, $baseurl);?>
    <div style="color:#aaa;font-size:13px;margin-top:8px;">* 鼠标悬停可查看全部内容</div>
</div>
<?php require_once 'footer.php'; ?>