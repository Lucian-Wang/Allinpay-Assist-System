<?php
$page_title = '编辑表单';
require_once '../header.php';
require_login();
$user = current_user();
$db = get_db();
$id = intval($_GET['id'] ?? 0);
$msg = '';
$stmt = $db->prepare("SELECT * FROM forms WHERE id=? AND user_id=?");
if (!$stmt) {
    die('SQL ERROR: ' . $db->error);
}
$stmt->bind_param('ii', $id, $user['id']);
$stmt->execute();
$forminfo = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$forminfo) exit('参数错误');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['desc'] ?? '');
    $merchant_id = intval($_POST['merchant_id'] ?? 0);
    $token = $_POST['csrf_token'] ?? '';
    $code = $forminfo['code']; // 短码不可被修改
    if (!csrf_check($token)) $msg = '表单超时，请刷新重试';
    elseif (!$name || !$merchant_id) $msg = '请填写完整信息';
    else {
        $now = date('Y-m-d H:i:s');
        $stmt2 = $db->prepare("UPDATE forms SET merchant_id=?, name=?, `desc`=?, updated_at=? WHERE id=? AND user_id=?");
        if (!$stmt2) {
            die('SQL ERROR: ' . $db->error);
        }
        $stmt2->bind_param('isssii', $merchant_id, $name, $desc, $now, $id, $user['id']);
        $stmt2->execute();
        redirect('index.php');
    }
}

// 商户下拉
$stmt = $db->prepare("SELECT id, name FROM merchants WHERE user_id=?");
if (!$stmt) {
    die('SQL ERROR: ' . $db->error);
}
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$merchants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<div class="content-card" style="max-width:600px;margin:48px auto;">
    <div class="card-header">
        <div class="card-title">编辑表单</div>
        <div class="card-actions"><a href="index.php">返回</a></div>
    </div>
    <?php if ($msg): ?><div class="alert" style="color:#d33;"><?=esc($msg)?></div><?php endif; ?>
    <form method="post" autocomplete="off">
        <div class="form-group" style="margin-bottom:24px;">
            <label>表单名称</label>
            <input type="text" name="name" required maxlength="50" value="<?=esc($forminfo['name'])?>" class="input" style="width:100%;padding:8px;">
        </div>
        <div class="form-group" style="margin-bottom:24px;">
            <label>表单描述</label>
            <input type="text" name="desc" maxlength="255" value="<?=esc($forminfo['desc'])?>" class="input" style="width:100%;padding:8px;">
        </div>
        <div class="form-group" style="margin-bottom:24px;">
            <label>选择商户</label>
            <div style="position:relative;">
                <select name="merchant_id" required class="custom-select" style="
                    width: 100%;
                    padding: 10px 36px 10px 12px;
                    border-radius: 6px;
                    border: 1px solid #2d8cf0;
                    font-size: 16px;
                    background: #f7fbff url('data:image/svg+xml;utf8,<svg fill=\'%232d8cf0\' height=\'20\' viewBox=\'0 0 20 20\' width=\'20\' xmlns=\'http://www.w3.org/2000/svg\'><path d=\'M7.293 7.293a1 1 0 011.414 0L10 8.586l1.293-1.293a1 1 0 111.414 1.414l-2 2a1 1 0 01-1.414 0l-2-2a1 1 0 010-1.414z\'></path></svg>') no-repeat right 1.1em top 1.3em;
                    appearance: none;
                    -webkit-appearance: none;
                    -moz-appearance: none;
                    box-shadow: 0 2px 8px rgba(45,140,240,0.09);
                    transition: border-color 0.2s;
                ">
                    <option value="">请选择</option>
                    <?php foreach ($merchants as $m): ?>
                    <option value="<?=$m['id']?>" <?=($forminfo['merchant_id']==$m['id'])?'selected':''?>><?=esc($m['name'])?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group" style="margin-bottom:24px;">
            <label>短码</label>
            <input type="text" name="code" value="<?=esc($forminfo['code'])?>" style="width:100%;padding:8px;background:#f5f6fa;color:#999;cursor:not-allowed;" readonly>
        </div>
        <input type="hidden" name="csrf_token" value="<?=csrf_token()?>">
        <button class="button" type="submit" style="width:100%;">保存</button>
    </form>
</div>
<?php require_once '../footer.php'; ?>