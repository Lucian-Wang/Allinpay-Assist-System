<?php
$page_title = '账单订单详情';
require_once '../header.php';
require_login();
$user = current_user();
$order_no = $_GET['order_no'] ?? '';
if (!$order_no) exit('参数错误');
$db = get_db();
$stmt = $db->prepare(
    "SELECT o.*, p.name as project_name 
     FROM bills_orders o 
     LEFT JOIN bills_projects p ON o.project_id=p.id
     WHERE o.order_no=? AND o.user_id=?"
);
$stmt->bind_param('si', $order_no, $user['id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$order) exit('订单不存在');
$status_text = [
    'pending' => '等待支付',
    'paid' => '支付成功',
    'expired' => '已失效'
];
?>
<div class="content-card" style="max-width:500px;margin:48px auto;">
  <div class="card-header">
    <div class="card-title">账单订单详情</div>
    <div class="card-actions"><a href="orders.php?project_id=<?=esc($order['project_id'])?>">返回订单列表</a></div>
  </div>
  <div style="padding:30px;">
    <div class="form-group" style="margin-bottom:12px;">订单号：<?=esc($order['order_no'])?></div>
    <div class="form-group" style="margin-bottom:12px;">通联交易商户单号：<?=esc($order['trxid'])?></div>
    <div class="form-group" style="margin-bottom:12px;">账单项目：<?=esc($order['project_name'])?></div>
    <div class="form-group" style="margin-bottom:12px;">客户标识：<?=esc($order['customer_tag'])?></div>
    <div class="form-group" style="margin-bottom:12px;">客户姓名：<?=esc($order['customer_name'])?></div>
    <div class="form-group" style="margin-bottom:12px;">金额：<span style="font-size:1.2em;color:#3183f4;"><?=number_format($order['amount'],2)?></span> 元</div>
    <div class="form-group" style="margin-bottom:12px;">状态：<?=esc($status_text[$order['status']] ?? $order['status'])?></div>
    <div class="form-group" style="margin-bottom:12px;">下单时间：<?=esc($order['created_at'])?></div>
    <div class="form-group" style="margin-bottom:12px;">支付时间：<?=esc($order['paid_at'])?></div>
    <?php if (!empty($order['remark'])): ?>
    <div class="form-group" style="margin-bottom:12px;">备注：<?=esc($order['remark'])?></div>
    <?php endif; ?>
  </div>
</div>
<?php require_once '../footer.php'; ?>