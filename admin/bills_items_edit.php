<?php
$page_title = '编辑账单明细';
require_once 'header.php';
$db = get_admin_db();

$id = intval($_GET['id'] ?? 0);
$msg = '';
$stmt = $db->prepare("SELECT * FROM bills_items WHERE id=?");
$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$item) exit('明细不存在');
$project_id = $item['project_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_tag = trim($_POST['customer_tag'] ?? '');
    $customer_name = trim($_POST['customer_name'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $remark = trim($_POST['remark'] ?? '');
    if (!$customer_tag || !$customer_name || $amount <= 0) {
        $msg = '请填写完整信息';
    } else {
        $now = date('Y-m-d H:i:s');
        $stmt = $db->prepare("UPDATE bills_items SET customer_tag=?, customer_name=?, amount=?, remark=?, updated_at=? WHERE id=?");
        $stmt->execute([$customer_tag, $customer_name, $amount, $remark, $now, $id]);
        header('Location: bills_items.php?project_id='.$project_id);
        exit;
    }
}
?>
<div class="content-card" style="max-width:420px;margin:48px auto;">
    <div class="card-header">
        <div class="card-title">编辑账单明细</div>
        <div class="card-actions"><a href="bills_items.php?project_id=<?=$project_id?>">返回</a></div>
    </div>
    <?php if ($msg): ?><div class="alert" style="color:#d33;"><?=admin_esc($msg)?></div><?php endif; ?>
    <form method="post" autocomplete="off">
        <div class="form-group" style="margin-bottom:18px;">
            <label>客户标识</label>
            <input type="text" name="customer_tag" maxlength="100" required value="<?=admin_esc($item['customer_tag'])?>" style="width:100%;padding:8px;">
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>客户姓名</label>
            <input type="text" name="customer_name" maxlength="100" required value="<?=admin_esc($item['customer_name'])?>" style="width:100%;padding:8px;">
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>金额</label>
            <input type="number" step="0.01" name="amount" min="0.01" required value="<?=admin_esc($item['amount'])?>" style="width:100%;padding:8px;">
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>备注</label>
            <input type="text" name="remark" maxlength="255" value="<?=admin_esc($item['remark'])?>" style="width:100%;padding:8px;">
        </div>
        <button class="button" type="submit" style="width:100%;">保存</button>
    </form>
</div>
<?php require_once 'footer.php'; ?>