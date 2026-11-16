<?php
$page_title = '编辑表单';
require_once 'header.php';
$db = get_admin_db();
$id = intval($_GET['id'] ?? 0);
$msg = '';
$stmt = $db->prepare("SELECT * FROM forms WHERE id=?");
$stmt->execute([$id]);
$form = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$form) exit('参数错误');

// 商户下拉
$merchants = $db->query("SELECT id, name FROM merchants ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
// 用户下拉
$users = $db->query("SELECT id, username FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['desc'] ?? '');
    $merchant_id = intval($_POST['merchant_id'] ?? 0);
    if (!$user_id || !$name || !$merchant_id) $msg = '请填写完整信息';
    else {
        $now = date('Y-m-d H:i:s');
        $stmt2 = $db->prepare("UPDATE forms SET user_id=?, merchant_id=?, name=?, `desc`=?, updated_at=? WHERE id=?");
        $stmt2->execute([$user_id, $merchant_id, $name, $desc, $now, $id]);
        header('Location: forms.php');
        exit;
    }
}
?>
<div class="content-card" style="max-width:600px;margin:48px auto;">
    <div class="card-header" style="margin-bottom:20px;">
        <span class="card-title">编辑表单</span>
        <span class="card-actions"><a href="forms.php">返回</a></span>
    </div>
    <?php if($msg): ?><div style="color:red;"><?=$msg?></div><?php endif;?>
    <form method="post" style="padding:0 8px;">
        <div class="form-group" style="margin-bottom:18px;">
            <label>归属用户*</label>
            <select name="user_id" required class="input">
                <option value="">请选择</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?=$u['id']?>" <?=$u['id']==$form['user_id']?'selected':''?>><?=admin_esc($u['username'])?>(<?=$u['id']?>)</option>
                <?php endforeach;?>
            </select>
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>表单名称*</label>
            <input type="text" name="name" required class="input" autocomplete="off" value="<?=admin_esc($form['name'])?>">
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>表单描述</label>
            <input type="text" name="desc" class="input" autocomplete="off" value="<?=admin_esc($form['desc'])?>">
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>选择商户*</label>
            <select name="merchant_id" required class="input">
                <option value="">请选择</option>
                <?php foreach ($merchants as $m): ?>
                    <option value="<?=$m['id']?>" <?=$m['id']==$form['merchant_id']?'selected':''?>><?=admin_esc($m['name'])?>(<?=$m['id']?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>短码</label>
            <input type="text" class="input" value="<?=admin_esc($form['code'])?>" readonly>
        </div>
        <button type="submit" class="button">保存</button>
    </form>
</div>
<?php require_once 'footer.php'; ?>