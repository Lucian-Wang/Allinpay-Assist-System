<?php
$page_title = '新建支付通道';
require_once '../header.php';
require_once 'channel_utils.php';
require_login();
$user = current_user();
$db = get_db();
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $merchant_id = intval($_POST['merchant_id'] ?? 0);
    $channel = random_channel_code($db, 8); // 后台自动生成
    if (!$name || !$merchant_id) $msg = '请填写完整信息';
    else {
        $now = date('Y-m-d H:i:s');
        $stmt2 = $db->prepare("INSERT INTO commonpay_channels (channel, name, merchant_id, status, created_at, updated_at) VALUES (?, ?, ?, 1, ?, ?)");
        $stmt2->bind_param('ssiss', $channel, $name, $merchant_id, $now, $now);
        $stmt2->execute();
        redirect('index.php');
    }
}
$stmt = $db->prepare("SELECT id, name FROM merchants WHERE user_id=?");
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$merchants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<div class="content-card" style="max-width:420px;margin:48px auto;">
    <div class="card-header">
        <div class="card-title">新建支付通道</div>
        <div class="card-actions"><a href="index.php">返回</a></div>
    </div>
    <?php if ($msg): ?><div class="alert" style="color:#d33;"><?=esc($msg)?></div><?php endif; ?>
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
                <option value="<?=$m['id']?>"><?=esc($m['name'])?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="button" type="submit" style="width:100%;">保存</button>
    </form>
</div>
<?php require_once '../footer.php'; ?>