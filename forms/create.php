<?php
$page_title = '新建表单';
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
        $stmt = $db->prepare("SELECT id FROM forms WHERE code=?");
        $stmt->bind_param('s', $str);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    } while ($exists);
    return $str;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['desc'] ?? '');
    $merchant_id = intval($_POST['merchant_id'] ?? 0);
    $code = random_code($db, 8); // 后端自动生成，不允许填写
    $token = $_POST['csrf_token'] ?? '';
    if (!csrf_check($token)) $msg = '表单超时，请刷新重试';
    elseif (!$name || !$merchant_id) $msg = '请填写完整信息';
    else {
        // 检查短码唯一（理论上random_code已保证唯一，这里可省略）
        $stmt = $db->prepare("SELECT id FROM forms WHERE code=?");
        $stmt->bind_param('s', $code);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) $msg = '短码生成失败，请重试';
        else {
            $now = date('Y-m-d H:i:s');
            // 同步codes表结构，补充created_at/updated_at/status字段
            $stmt2 = $db->prepare("INSERT INTO forms (user_id, merchant_id, name, `desc`, code, created_at, updated_at, status) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt2->bind_param('iisssss', $user['id'], $merchant_id, $name, $desc, $code, $now, $now);
            $stmt2->execute();
            redirect('index.php');
        }
        $stmt->close();
    }
}

// 商户下拉
$stmt = $db->prepare("SELECT id, name FROM merchants WHERE user_id=?");
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$merchants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<style>
    body { background: #f7fafd; }
    .content-card {
        max-width: 600px;
        margin: 48px auto;
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 8px 36px rgba(44,104,255,0.10);
        padding: 36px 36px 28px 36px;
        font-size: 18px;
    }
    .card-header { display: flex; justify-content: space-between; align-items: center; }
    .card-title { font-size: 28px; font-weight: bold; color: #2486ff; }
    .card-actions a { color: #2486ff; text-decoration: none; font-size: 18px; }
    .form-group { margin-bottom: 28px; }
    .form-group label { display:inline-block; min-width:110px; }
    .input, .custom-select {
        width: 100%;
        padding: 14px 14px;
        border-radius: 8px;
        border: 1.5px solid #e3eaf5;
        background: #f7fafd;
        font-size: 18px;
        outline: none;
        box-sizing: border-box;
        margin-top: 8px;
        transition: border 0.2s;
    }
    .input:focus, .custom-select:focus { border: 1.5px solid #2486ff; background: #fff; }
    .custom-select {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background-position: right 1.1em top 1.5em, right 1.7em top 1.5em;
        background-size: 0.65em 0.65em, 0.65em 0.65em;
        background-repeat: no-repeat;
    }
    .button {
        background: linear-gradient(90deg, #2486ff 0%, #55aaff 100%);
        color: #fff;
        border: none;
        border-radius: 8px;
        font-size: 20px;
        font-weight: bold;
        padding: 12px 0;
        width: 120px;
        cursor: pointer;
        margin-top: 12px;
        transition: background 0.18s;
    }
    .button:hover { background: #2486ff; }
    @media (max-width: 600px) {
        .content-card { padding: 18px 4vw 12px 4vw; }
        .card-title { font-size: 22px; }
        .button { font-size: 17px; width: 100%; }
    }
</style>
<div class="content-card">
    <div class="card-header" style="margin-bottom:20px;">
        <span class="card-title">新建表单</span>
        <span class="card-actions"><a href="index.php">返回</a></span>
    </div>
    <?php if($msg): ?><div style="color:red;"><?=$msg?></div><?php endif;?>
    <form method="post" style="padding:0 8px;">
        <div class="form-group">
            <label for="form-name">表单名称*：</label>
            <input type="text" name="name" id="form-name" required class="input" autocomplete="off" placeholder="请输入表单名称">
        </div>
        <div class="form-group">
            <label for="form-desc">表单描述：</label>
            <input type="text" name="desc" id="form-desc" class="input" autocomplete="off" placeholder="请输入描述">
        </div>
        <div class="form-group">
            <label for="merchant_id">选择商户*：</label>
            <select name="merchant_id" id="merchant_id" required class="custom-select">
                <option value="">请选择</option>
                <?php foreach ($merchants as $m): ?>
                    <option value="<?=$m['id']?>"><?=$m['name']?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <!-- 短码由系统自动生成，用户不可见不可输入 -->
        <input type="hidden" name="csrf_token" value="<?=csrf_token()?>">
        <button type="submit" class="button">保存</button>
    </form>
</div>
<?php require_once '../footer.php'; ?>