<?php
require_once 'header.php';
$order_no = $_GET['order_no'] ?? '';
if (!$order_no) exit('参数错误');
$db = get_admin_db();
$stmt = $db->prepare("SELECT o.*, c.name as code_name, u.username as user_name, m.name as merchant_name
    FROM code_orders o
    LEFT JOIN codes c ON o.code_id=c.id
    LEFT JOIN users u ON o.user_id=u.id
    LEFT JOIN merchants m ON o.merchant_id=m.id
    WHERE o.order_no=?");
$stmt->execute([$order_no]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) exit('订单不存在');
$status_text = [
    'pending' => '等待支付',
    'paid' => '支付成功',
    'expired' => '已失效'
];
?>
<style>
.content-card {
    max-width: 520px;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 32px #0001;
    margin: 48px auto;
    padding: 32px 32px 18px 32px;
}
.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 22px;
}
.card-title {
    font-size: 1.4em;
    font-weight: 600;
    color: #222;
    letter-spacing: 1px;
}
.card-actions {
    font-size: 15px;
}
.form-group {
    margin-bottom: 14px;
    font-size: 16px;
    color: #222;
}
.form-group span {
    color: #3183f4;
    font-size: 1.15em;
    font-weight: bold;
}
@media (max-width: 700px){
    .content-card {padding:10px;}
    .card-title {font-size:1.1rem;}
    .form-group{font-size:15px;}
}
</style>
<div class="content-card">
    <div class="card-header">
        <div class="card-title">订单详情</div>
        <div class="card-actions"><a href="javascript:history.back();">返回订单列表</a></div>
    </div>
    <div style="padding:20px 0 10px 0;">
        <div class="form-group">订单号：<?=admin_esc($order['order_no'])?></div>
        <div class="form-group">通联交易商户单号：<?=admin_esc($order['trxid'])?></div>
        <div class="form-group">卡台名称：<?=admin_esc($order['code_name'])?></div>
        <div class="form-group">归属用户：<?=admin_esc($order['user_name'])?></div>
        <div class="form-group">商户：<?=admin_esc($order['merchant_name'])?></div>
        <div class="form-group">金额：<span><?=number_format($order['amount'],2)?></span> 元</div>
        <div class="form-group">状态：
            <?php if ($order['status']=='paid'): ?>
                <span style="color:green;">支付成功</span>
            <?php elseif ($order['status']=='pending'): ?>
                <span style="color:#faad14;">等待支付</span>
            <?php else: ?>
                <span style="color:#aaa;"><?=admin_esc($status_text[$order['status']] ?? $order['status'])?></span>
            <?php endif; ?>
        </div>
        <div class="form-group">下单时间：<?=admin_esc($order['created_at'])?></div>
        <div class="form-group">支付时间：<?=admin_esc($order['paid_at'])?></div>
        <?php if (!empty($order['remark'])): ?>
        <div class="form-group">备注：<?=admin_esc($order['remark'])?></div>
        <?php endif; ?>
    </div>
</div>
<?php require_once 'footer.php'; ?>