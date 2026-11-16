<?php
require_once '../db.php';
require_once '../utils.php';

$order_no = $_GET['order_no'] ?? '';
if (!$order_no) exit('订单号缺失');
$db = get_db();
$stmt = $db->prepare("SELECT * FROM code_orders WHERE order_no=?");
$stmt->bind_param('s', $order_no);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$order) exit('订单不存在');
?>
<div class="content-card" style="max-width:420px;margin:48px auto;">
    <div class="card-header">
        <div class="card-title">订单支付状态</div>
    </div>
    <div style="text-align:center;margin:24px 0;">
        <b>订单号：</b><?=esc($order['order_no'])?><br>
        <b>金额：</b><?=esc($order['amount'])?> 元<br>
        <b>状态：</b>
        <?php
        if ($order['status'] == 'paid') {
            echo '<span style="color:green;">已支付</span>';
        } else {
            echo '<span style="color:orange;">待支付</span>';
        }
        ?>
    </div>
    <?php if ($order['status'] != 'paid'): ?>
    <script>
        setTimeout(function(){ location.reload(); }, 3000);
    </script>
    <div style="text-align:center;">页面每3秒自动刷新</div>
    <?php endif; ?>
</div>
<?php require_once '../footer.php'; ?>