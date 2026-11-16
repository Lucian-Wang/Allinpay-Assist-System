<?php
$page_title = '编辑支付通道';
require_once 'header.php';
$db = get_admin_db();
$id = intval($_GET['id'] ?? 0);
$msg = '';
$stmt = $db->prepare("SELECT * FROM commonpay_channels WHERE id=?");
$stmt->execute([$id]);
$info = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$info) exit('参数错误');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $merchant_id = intval($_POST['merchant_id'] ?? 0);
    if (!$name || !$merchant_id) $msg = '请填写完整信息';
    else {
        $now = date('Y-m-d H:i:s');
        $stmt2 = $db->prepare("UPDATE commonpay_channels SET name=?, merchant_id=?, updated_at=? WHERE id=?");
        $stmt2->execute([$name, $merchant_id, $now, $id]);
        header('Location: commonpay.php');
        exit;
    }
}
$merchants = $db->query("SELECT id, name FROM merchants ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="content-card" style="max-width:420px;margin:48px auto;">
    <div class="card-header">
        <div class="card-title">编辑支付通道</div>
        <div class="card-actions"><a href="commonpay.php">返回</a></div>
    </div>
    <?php if ($msg): ?><div class="alert" style="color:#d33;"><?=admin_esc($msg)?></div><?php endif; ?>
    <form method="post" autocomplete="off">
        <div class="form-group" style="margin-bottom:18px;">
            <label>通道标识</label>
            <input type="text" value="<?=admin_esc($info['channel'])?>" readonly style="width:100%;padding:8px;background:#eee;">
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>通道名称</label>
            <input type="text" name="name" maxlength="64" required value="<?=admin_esc($info['name'])?>" style="width:100%;padding:8px;">
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>归属商户</label>
            <select name="merchant_id" required style="width:100%;padding:8px;">
                <option value="">请选择</option>
                <?php foreach ($merchants as $m): ?>
                <option value="<?=$m['id']?>" <?=($info['merchant_id']==$m['id']?'selected':'')?>><?=admin_esc($m['name'])?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="button" type="submit" style="width:100%;">保存</button>
    </form>
</div>
<?php require_once 'footer.php'; ?>