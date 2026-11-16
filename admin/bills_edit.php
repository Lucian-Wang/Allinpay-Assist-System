<?php
$page_title = '编辑账单项目';
require_once 'header.php';
$db = get_admin_db();

$id = intval($_GET['id'] ?? 0);
$msg = '';
$stmt = $db->prepare("SELECT * FROM bills_projects WHERE id=?");
$stmt->execute([$id]);
$info = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$info) exit('参数错误');

// 商户下拉
$merchants = $db->query("SELECT id, name FROM merchants ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
// 用户下拉
$users = $db->query("SELECT id, username FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $merchant_id = intval($_POST['merchant_id'] ?? 0);
    $desc = trim($_POST['desc'] ?? '');
    $status = intval($_POST['status'] ?? 1);
    if (!$user_id || !$name || !$merchant_id) $msg = '请填写完整信息';
    else {
        $now = date('Y-m-d H:i:s');
        $stmt2 = $db->prepare("UPDATE bills_projects SET user_id=?, merchant_id=?, name=?, `desc`=?, status=?, updated_at=? WHERE id=?");
        $stmt2->execute([$user_id, $merchant_id, $name, $desc, $status, $now, $id]);
        header('Location: bills.php');
        exit;
    }
}
?>
<div class="content-card" style="max-width:420px;margin:48px auto;">
    <div class="card-header">
        <div class="card-title">编辑账单项目</div>
        <div class="card-actions"><a href="bills.php">返回</a></div>
    </div>
    <?php if ($msg): ?><div class="alert" style="color:#d33;"><?=admin_esc($msg)?></div><?php endif; ?>
    <form method="post" autocomplete="off">
        <div class="form-group" style="margin-bottom:18px;">
            <label>归属用户</label>
            <select name="user_id" required style="width:100%;padding:8px;">
                <option value="">请选择用户</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?=$u['id']?>" <?=$u['id']==$info['user_id']?'selected':''?>><?=admin_esc($u['username'])?>(<?=$u['id']?>)</option>
                <?php endforeach;?>
            </select>
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>项目名称</label>
            <input type="text" name="name" required maxlength="100" value="<?=admin_esc($info['name'])?>" style="width:100%;padding:8px;">
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>选择商户</label>
            <select name="merchant_id" required style="width:100%;padding:8px;">
                <option value="">请选择商户</option>
                <?php foreach ($merchants as $m): ?>
                <option value="<?=$m['id']?>" <?=$m['id']==$info['merchant_id']?'selected':''?>><?=admin_esc($m['name'])?>(<?=$m['id']?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>描述</label>
            <input type="text" name="desc" maxlength="255" value="<?=admin_esc($info['desc'])?>" style="width:100%;padding:8px;">
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>短码</label>
            <input type="text" name="code" value="<?=admin_esc($info['code'])?>" style="width:100%;padding:8px;background:#f5f6fa;color:#999;cursor:not-allowed;" readonly>
        </div>
        <button class="button" type="submit" style="width:100%;">保存</button>
    </form>
</div>
<?php require_once 'footer.php'; ?>