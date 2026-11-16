<?php
$page_title = '新建账单项目';
require_once '../header.php';
require_login();
$user = current_user();
$db = get_db();
$msg = '';

// 生成唯一8位大写字母+数字短码
function random_code($db, $length = 8) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    do {
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $stmt = $db->prepare("SELECT id FROM bills_projects WHERE code=?");
        $stmt->bind_param('s', $str);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    } while ($exists);
    return $str;
}

// 商户下拉
$stmt = $db->prepare("SELECT id, name FROM merchants WHERE user_id=?");
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$merchants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $merchant_id = intval($_POST['merchant_id'] ?? 0);
    $desc = trim($_POST['desc'] ?? '');
    $status = intval($_POST['status'] ?? 1);
    $token = $_POST['csrf_token'] ?? '';
    $code = random_code($db, 8); // 后端自动生成，不允许填写
    if (!csrf_check($token)) $msg = '表单超时，请刷新重试';
    elseif (!$name || !$merchant_id) $msg = '请填写完整信息';
    else {
        // 检查短码唯一（理论上random_code已保证唯一，这里可省略）
        $stmt = $db->prepare("SELECT id FROM bills_projects WHERE code=?");
        $stmt->bind_param('s', $code);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) $msg = '短码生成失败，请重试';
        else {
            $now = date('Y-m-d H:i:s');
            $stmt2 = $db->prepare("INSERT INTO bills_projects (user_id, merchant_id, name, code, `desc`, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt2->bind_param('iisssiss', $user['id'], $merchant_id, $name, $code, $desc, $status, $now, $now);
            $stmt2->execute();
            redirect('index.php');
        }
        $stmt->close();
    }
}
?>
<div class="content-card" style="max-width:420px;margin:48px auto;">
    <div class="card-header">
        <div class="card-title">新建账单项目</div>
        <div class="card-actions"><a href="index.php">返回</a></div>
    </div>
    <?php if ($msg): ?><div class="alert" style="color:#d33;"><?=esc($msg)?></div><?php endif; ?>
    <form method="post" autocomplete="off">
        <div class="form-group" style="margin-bottom:18px;">
            <label>项目名称</label>
            <input type="text" name="name" required maxlength="100" style="width:100%;padding:8px;">
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>选择商户</label>
            <div style="position:relative;">
                <select name="merchant_id" required style="
                    width: 100%;
                    padding: 10px 36px 10px 12px;
                    border-radius: 6px;
                    border: 1px solid #2d8cf0;
                    font-size: 16px;
                    background: #f7fbff;
                    appearance: none;
                    -webkit-appearance: none;
                    -moz-appearance: none;
                    box-shadow: 0 2px 8px rgba(45,140,240,0.09);
                    transition: border-color 0.2s;
                ">
                    <option value="">请选择</option>
                    <?php foreach ($merchants as $m): ?>
                    <option value="<?=$m['id']?>"><?=esc($m['name'])?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>描述</label>
            <input type="text" name="desc" maxlength="255" style="width:100%;padding:8px;">
        </div>
        <!-- 短码由系统自动生成，用户不可见不可输入 -->
        <input type="hidden" name="csrf_token" value="<?=csrf_token()?>">
        <button class="button" type="submit" style="width:100%;">保存</button>
    </form>
</div>
<?php require_once '../footer.php'; ?>