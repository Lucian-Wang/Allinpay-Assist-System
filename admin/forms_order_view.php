<?php
$page_title = '订单详情';
require_once 'header.php';
$db = get_admin_db();
$order_no = $_GET['order_no'] ?? '';
if (!$order_no) exit('参数错误');

$stmt = $db->prepare("SELECT o.*, f.name as form_name FROM form_orders o LEFT JOIN forms f ON o.form_id=f.id WHERE o.order_no=?");
$stmt->execute([$order_no]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) exit('订单不存在');
$status_text = [
    'pending' => '等待支付',
    'paid' => '支付成功',
    'expired' => '已失效'
];
// 解析自定义字段
$field_values = [];
if (!empty($order['field_values'])) {
    $field_values = @json_decode($order['field_values'], true);
    if (!is_array($field_values)) $field_values = [];
}
?>
<div class="content-card" style="max-width:500px;margin:48px auto;">
    <div class="card-header">
        <div class="card-title">订单详情</div>
        <div class="card-actions"><a href="forms_orders.php?form_id=<?=admin_esc($order['form_id'])?>">返回订单列表</a></div>
    </div>
    <div style="padding:30px;">
        <div class="form-group" style="margin-bottom:12px;">订单号：<?=admin_esc($order['order_no'])?></div>
        <div class="form-group" style="margin-bottom:12px;">表单名称：<?=admin_esc($order['form_name'])?></div>
        <div class="form-group" style="margin-bottom:12px;">金额：<span style="font-size:1.2em;color:#3183f4;"><?=number_format($order['amount'],2)?></span> 元</div>
        <div class="form-group" style="margin-bottom:12px;">状态：<?=admin_esc($status_text[$order['status']] ?? $order['status'])?></div>
        <div class="form-group" style="margin-bottom:12px;">下单时间：<?=admin_esc($order['created_at'])?></div>
        <div class="form-group" style="margin-bottom:12px;">支付时间：<?=admin_esc($order['paid_at'])?></div>
        <?php if (!empty($order['remark'])): ?>
        <div class="form-group" style="margin-bottom:12px;">备注：<?=admin_esc($order['remark'])?></div>
        <?php endif; ?>
        <?php if (!empty($field_values)): ?>
            <div class="form-group" style="margin-bottom:12px;">
                <b>自定义字段：</b><br>
                <?php foreach ($field_values as $label => $val): ?>
                    <?=admin_esc($label)?>：<?=admin_esc($val)?><br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once 'footer.php'; ?>