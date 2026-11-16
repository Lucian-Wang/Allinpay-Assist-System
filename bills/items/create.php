<?php
$page_title = '新建账单明细';
require_once '../../header.php';
require_login();
$user = current_user();
$db = get_db();

$project_id = intval($_GET['project_id'] ?? 0);
if (!$project_id) exit('参数错误');
$stmt = $db->prepare("SELECT * FROM bills_projects WHERE id=? AND user_id=?");
$stmt->bind_param('ii', $project_id, $user['id']);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$project) exit('账单项目不存在');

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_tag = trim($_POST['customer_tag'] ?? '');
    $customer_name = trim($_POST['customer_name'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $remark = trim($_POST['remark'] ?? '');
    if (!$customer_tag || !$amount) $msg = '客户标识和应收金额必填';
    else {
        $stmt = $db->prepare("INSERT INTO bills_items (project_id, user_id, customer_tag, customer_name, amount, remark, status, created_at, updated_at) VALUES (?,?,?,?,?,?,1,NOW(),NOW())");
        $stmt->bind_param('iissds', $project_id, $user['id'], $customer_tag, $customer_name, $amount, $remark);
        $stmt->execute();
        $stmt->close();
        header('Location: index.php?project_id='.$project_id);
        exit;
    }
}
?>
<div class="content-card" style="max-width:440px;margin:48px auto;">
    <div class="card-header">
        <div class="card-title">新建账单明细</div>
        <div class="card-actions">
            <a href="index.php?project_id=<?=$project_id?>">返回</a>
        </div>
    </div>
    <?php if ($msg): ?><div class="alert" style="color:#d33;"><?=esc($msg)?></div><?php endif; ?>
    <form method="post" autocomplete="off" style="padding:30px;">
        <div class="form-group" style="margin-bottom:20px;">
            <label>客户标识（如房号/学号/手机号）</label>
            <input type="text" name="customer_tag" required maxlength="64" class="form-input">
        </div>
        <div class="form-group" style="margin-bottom:20px;">
            <label>客户姓名</label>
            <input type="text" name="customer_name" maxlength="100" class="form-input">
        </div>
        <div class="form-group" style="margin-bottom:20px;">
            <label>应收金额</label>
            <input type="number" step="0.01" name="amount" required class="form-input" style="color:#3183f4;background:#f7fbff;">
        </div>
        <div class="form-group" style="margin-bottom:20px;">
            <label>备注</label>
            <input type="text" name="remark" maxlength="255" class="form-input">
        </div>
        <button class="button" type="submit" style="
            width:100%;padding:14px 0;font-size:18px;
            color:#fff;background:#3183f4;border:none;border-radius:8px;
            box-shadow:0 4px 16px rgba(49,131,244,0.08);cursor:pointer;transition:all .2s;">
            + 创建明细
        </button>
    </form>
</div>
<style>
.form-input {
    width: 100%;
    height: 48px;
    padding: 0 12px;
    border-radius: 6px;
    border: 1px solid #2d8cf0;
    font-size: 1rem;
    background: #f8fbff;
    box-sizing: border-box;
}
.form-input:focus {
    outline: none;
    border-color: #3183f4;
    background: #f7fbff;
}
</style>
<?php require_once '../../footer.php'; ?>