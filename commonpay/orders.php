<?php
$page_title = '通道订单列表';
require_once '../header.php';
require_login();
$user = current_user();
$db = get_db();

// 获取参数
$channel_id = intval($_GET['channel_id'] ?? 0);
$kw = trim($_GET['kw'] ?? '');

// 构造where条件
$where = "WHERE 1";
$params = [];
$types = "";

// 只显示当前用户自己的订单（通过商户归属控制）
$where .= " AND m.user_id=?";
$params[] = $user['id'];
$types .= "i";

// 如果有channel_id则限定通道
if ($channel_id) {
    $where .= " AND o.channel_id=?";
    $params[] = $channel_id;
    $types .= "i";
}

// 搜索条件
if ($kw !== '') {
    $where .= " AND (o.pay_order_no LIKE ? OR o.subject LIKE ? OR o.user_id LIKE ? OR o.biz_order_no LIKE ?)";
    $like_kw = "%{$kw}%";
    $params = array_merge($params, [$like_kw, $like_kw, $like_kw, $like_kw]);
    $types .= 'ssss';
}

// 分页
$page = max(1, intval($_GET['page'] ?? 1));
$page_size = 20;
$offset = ($page - 1) * $page_size;

// 统计总数
$sql_count = "SELECT COUNT(*) FROM commonpay_orders o LEFT JOIN merchants m ON o.merchant_id=m.id $where";
$stmt = $db->prepare($sql_count);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$stmt->bind_result($total_count);
$stmt->fetch();
$stmt->close();

// 查询订单
$sql = "SELECT o.*, m.name AS merchant_name, c.name AS channel_name FROM commonpay_orders o 
        LEFT JOIN merchants m ON o.merchant_id=m.id 
        LEFT JOIN commonpay_channels c ON o.channel_id=c.id
        $where
        ORDER BY o.id DESC 
        LIMIT ? OFFSET ?";
$params_query = array_merge($params, [$page_size, $offset]);
$stmt = $db->prepare($sql);
$stmt->bind_param($types . 'ii', ...$params_query);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 最小化显示
function escmini($s, $len=12) {
    $s = esc($s ?? '');
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
$baseurl = 'orders.php?';
if ($channel_id) $baseurl .= 'channel_id=' . intval($channel_id) . '&';
if ($kw !== '') $baseurl .= 'kw=' . urlencode($kw) . '&';

// 获取通道下拉
$channels = [];
$stmt = $db->prepare("SELECT c.id,c.name FROM commonpay_channels c LEFT JOIN merchants m ON c.merchant_id=m.id WHERE m.user_id=? ORDER BY c.id DESC");
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $channels[] = $row;
}
$stmt->close();
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
    /* 美化select，去除系统下拉箭头和拉条 */
    .card-actions select {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background: #fff url("data:image/svg+xml;charset=UTF-8,<svg width='12' height='8' xmlns='http://www.w3.org/2000/svg'><path d='M1 1l5 5 5-5' stroke='%233183f4' stroke-width='2' fill='none' fill-rule='evenodd'/></svg>") no-repeat right 0.7em center/1em;
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
    /* 隐藏select的滚动条（主要针对IE/Edge/Chrome） */
    .card-actions select::-ms-expand {
        display: none;
    }
    .card-actions select::-webkit-scrollbar {
        display: none;
    }
    /* 隐藏横向滚动条 */
    .content-card > div[style*='overflow-x:auto']::-webkit-scrollbar {
        height: 0 !important;
    }
    .content-card > div[style*='overflow-x:auto'] {
        scrollbar-width: none; /* Firefox */
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
            <form method="get" action="orders.php" style="display:inline-block;margin-right:14px;">
                <select name="channel_id" onchange="this.form.submit()">
                    <option value="0">全部通道</option>
                    <?php foreach($channels as $c): ?>
                        <option value="<?=esc($c['id'])?>" <?=($channel_id==$c['id'])?'selected':''?>><?=esc($c['name'])?></option>
                    <?php endforeach;?>
                </select>
                <input type="text" name="kw" value="<?=esc($kw)?>" placeholder="订单号/商品/用户/业务单号" style="padding:6px 12px;width:180px;">
                <button type="submit" class="btn">搜索</button>
            </form>
            <a href="index.php" class="btn">返回通道管理</a>
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
                <td><?=esc($o['id'])?></td>
                <td title="<?=esc($o['pay_order_no'])?>"><?=escmini($o['pay_order_no'], 12)?></td>
                <td title="<?=esc($o['channel_name'])?>"><?=escmini($o['channel_name'], 6)?></td>
                <td title="<?=esc($o['merchant_name'])?>"><?=escmini($o['merchant_name'], 6)?></td>
                <td><?=number_format($o['amount'],2)?></td>
                <td title="<?=esc($o['subject'])?>"><?=escmini($o['subject'], 7)?></td>
                <td><?=esc($o['status'])?></td>
                <td title="<?=esc($o['allinpay_trxid'])?>"><?=escmini($o['allinpay_trxid'], 10)?></td>
                <td><?=escmini($o['paid_at'], 10)?></td>
                <td><a href="order_view.php?pay_order_no=<?=esc($o['pay_order_no'])?>">详情</a></td>
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
<?php require_once '../footer.php'; ?>