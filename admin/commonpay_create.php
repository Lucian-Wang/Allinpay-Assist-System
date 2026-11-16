<?php
$page_title = '新建支付通道';
require_once 'header.php';
$msg = '';
$db = get_admin_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $merchant_id = intval($_POST['merchant_id'] ?? 0);
    $channel = random_channel_code($db, 8); // 需确保工具函数已引入
    if (!$name || !$merchant_id) $msg = '请填写完整信息';
    else {
        $now = date('Y-m-d H:i:s');
        $stmt2 = $db->prepare("INSERT INTO commonpay_channels (channel, name, merchant_id, status, created_at, updated_at) VALUES (?, ?, ?, 1, ?, ?)");
        $stmt2->execute([$channel, $name, $merchant_id, $now, $now]);
        header('Location: commonpay.php');
        exit;
    }
}
$merchants = $db->query("SELECT id, name FROM merchants ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="content-card" style="max-width:420px;margin:48px auto;">
    <div class="card-header">
        <div class="card-title">新建支付通道</div>
        <div class="card-actions"><a href="commonpay.php">返回</a></div>
    </div>
    <?php if ($msg): ?><div class="alert" style="color:#d33;"><?=admin_esc($msg)?></div><?php endif; ?>
    <form method="post" autocomplete="off">
        <div class="form-group" style="margin-bottom:18px;">
            <label>通道标识（系统自动生成）</label>
            <input type="text" value="保存后自动生成" readonly style="width:100%;padding:8px;background:#eee;">
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>通道名称</label>
            <input type="text" name="name" maxlength="64" required style="width:100%;padding:8px;">
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>归属商户</label>
            <select name="merchant_id" required style="width:100%;padding:8px;">
                <option value="">请选择</option>
                <?php foreach ($merchants as $m): ?>
                <option value="<?=$m['id']?>"><?=admin_esc($m['name'])?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="button" type="submit" style="width:100%;">保存</button>
    </form>
</div>
<?php require_once 'footer.php'; ?>