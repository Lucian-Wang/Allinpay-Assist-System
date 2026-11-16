<?php
require_once '../db.php';
$code = $_GET['code'] ?? '';
$item_id = intval($_GET['item_id'] ?? 0);

if (!$code) exit('参数错误');

$db = get_db();

if ($item_id) {
    // 方式1：传了code+item_id，直接查明细
    $sql = "SELECT i.*, p.name AS project_name, p.code AS project_code, p.desc AS project_desc, m.name AS merchant_name
            FROM bills_items i
            LEFT JOIN bills_projects p ON i.project_id = p.id
            LEFT JOIN merchants m ON p.merchant_id = m.id
            WHERE i.id=? AND p.code=? AND i.status=1 AND p.status=1 AND i.pay_status=0";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('is', $item_id, $code);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$item) {
        // 友好提示
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>账单不可用</title>
            <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
            <style>
                body {background: #eef4fb;font-family: 'Segoe UI','PingFang SC','Arial',sans-serif;margin:0;padding:0;min-height:100vh;}
                .error-container {width:100%;max-width:400px;margin:100px auto 0 auto;background:#fff;border-radius:18px;box-shadow:0 8px 36px rgba(44,104,255,0.10);padding:36px 24px 32px 24px;}
                .error-icon {font-size:54px;color:#ff9800;margin-bottom:18px;}
                .error-title {font-size:22px;color:#f44336;font-weight:bold;margin-bottom:10px;}
                .error-message {font-size:16px;color:#666;margin-bottom:22px;}
                .back-btn {display:inline-block;padding:10px 32px;background:linear-gradient(90deg,#1677ff 30%,#269fff 100%);color:#fff;border-radius:8px;text-decoration:none;font-size:16px;}
                .back-btn:hover {background:#135edb;}
            </style>
        </head>
        <body>
            <div class="error-container">
                <div class="error-icon">⚠️</div>
                <div class="error-title">账单不可用</div>
                <div class="error-message">
                    抱歉，该账单不存在、已被禁用或已支付。<br>
                    请联系收款方或返回重新扫码。
                </div>
                <a href="javascript:history.back();" class="back-btn">返回上一页</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    function get_pay_env() {
        $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
        if (strpos($ua, 'micromessenger') !== false) return 'wechat';
        if (strpos($ua, 'alipayclient') !== false) return 'alipay';
        return 'pc';
    }
    $env = get_pay_env();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title><?= htmlspecialchars($item['merchant_name']) ?> - 账单收银台</title>
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
        <style>
            body {background: #eef4fb;font-family: 'Segoe UI','PingFang SC','Arial',sans-serif;margin:0;padding:0;min-height:100vh;}
            .pay-container {width:100%;max-width:430px;margin:60px auto 0 auto;background:#fff;border-radius:28px;box-shadow:0 8px 36px rgba(44,104,255,0.10);padding:38px 30px 24px 30px;}
            .merchant-logo {width:54px;height:54px;border-radius:16px;background:#e9f2ff;display:flex;align-items:center;justify-content:center;margin:0 auto 13px auto;font-size:24px;color:#269fff;}
            .merchant-title {font-size:23px;font-weight:600;color:#2d8cf0;text-align:center;margin-bottom:2px;}
            .project-code {color:#939393;font-size:16px;text-align:center;margin-bottom:2px;}
            .project-desc {color:#a7a7a7;font-size:15px;text-align:center;margin-bottom:16px;}
            .bill-info {font-size:17px;color:#555;margin-bottom:12px;}
            .amount-label {font-size:16px;color:#444;margin-bottom:4px;}
            .amount-value {font-size:28px;color:#ff8200;font-weight:700;margin-bottom:7px;}
            .btn-row {display:flex;justify-content:center;gap:20px;width:100%;margin-top:24px;}
            .pay-btn {flex:1;font-size:19px;font-weight:500;padding:13px 0;border:none;border-radius:12px;color:#fff;cursor:pointer;transition:background 0.18s, box-shadow 0.15s;box-shadow:0 2px 10px rgba(44,104,255,0.07);}
            .pay-btn.wx {background:linear-gradient(90deg,#21c08b 30%,#16cfa8 100%);}
            .pay-btn.wx:active {background:#179e72;}
            .pay-btn.ali {background:linear-gradient(90deg,#1677ff 30%,#269fff 100%);}
            .pay-btn.ali:active {background:#135edb;}
            .pay-footer {margin-top:32px;color:#b5b5b5;font-size:13px;letter-spacing:1px;text-align:center;}
            @media (max-width:500px) {.pay-container{margin:18px auto 0 auto;padding:15px 3vw 12px 3vw;}.amount-value{font-size:20px;}.merchant-logo{width:36px;height:36px;font-size:16px;}}
        </style>
    </head>
    <body>
    <div class="pay-container">
        <div class="merchant-logo"><?= mb_substr($item['merchant_name'] ?? '账', 0, 1, 'utf-8') ?></div>
        <div class="merchant-title"><?= htmlspecialchars($item['merchant_name']) ?></div>
        <div class="project-code"><?= htmlspecialchars($item['project_name']) ?></div>
        <?php if($item['project_desc']) { ?>
            <div class="project-desc"><?= htmlspecialchars($item['project_desc']) ?></div>
        <?php } ?>
        <div class="bill-info">
            客户：<?=htmlspecialchars($item['customer_name'])?><br>
            标识：<?=htmlspecialchars($item['customer_tag'])?><br>
            账单：<?=htmlspecialchars($item['remark'])?><br>
        </div>
        <div class="amount-label">应付金额</div>
        <div class="amount-value"><?=number_format($item['amount'],2)?> 元</div>
        <form action="pay_action.php" method="post" style="width:100%;">
            <input type="hidden" name="code" value="<?= htmlspecialchars($code) ?>">
            <input type="hidden" name="item_id" value="<?= htmlspecialchars($item_id) ?>">
            <div class="btn-row">
                <?php if ($env === 'wechat'): ?>
                    <button class="pay-btn wx" type="submit" name="paytype" value="W01">微信支付</button>
                <?php elseif ($env === 'alipay'): ?>
                    <button class="pay-btn ali" type="submit" name="paytype" value="A01">支付宝支付</button>
                <?php else: ?>
                    <button class="pay-btn wx" type="submit" name="paytype" value="W01">微信支付</button>
                    <button class="pay-btn ali" type="submit" name="paytype" value="A01">支付宝支付</button>
                <?php endif; ?>
            </div>
        </form>
        <div class="pay-footer">&copy; <?=date('Y')?> 助理Pro | 账单收款</div>
    </div>
    </body>
    </html>
    <?php
    exit;
}

// 方式2：只传了code，查项目，显示输入标识表单
$sql = "SELECT p.*, m.name AS merchant_name FROM bills_projects p LEFT JOIN merchants m ON p.merchant_id=m.id WHERE p.code=? AND p.status=1";
$stmt = $db->prepare($sql);
$stmt->bind_param('s', $code);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$project) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>账单收款码不可用</title>
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
        <style>
            body {background: #eef4fb;font-family: 'Segoe UI','PingFang SC','Arial',sans-serif;margin:0;padding:0;min-height:100vh;}
            .error-container {width:100%;max-width:400px;margin:100px auto 0 auto;background:#fff;border-radius:18px;box-shadow:0 8px 36px rgba(44,104,255,0.10);padding:36px 24px 32px 24px;}
            .error-icon {font-size:54px;color:#ff9800;margin-bottom:18px;}
            .error-title {font-size:22px;color:#f44336;font-weight:bold;margin-bottom:10px;}
            .error-message {font-size:16px;color:#666;margin-bottom:22px;}
            .back-btn {display:inline-block;padding:10px 32px;background:linear-gradient(90deg,#1677ff 30%,#269fff 100%);color:#fff;border-radius:8px;text-decoration:none;font-size:16px;}
            .back-btn:hover {background:#135edb;}
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">⚠️</div>
            <div class="error-title">账单收款码不可用</div>
            <div class="error-message">
                抱歉，该项目收款码不存在或已被禁用。<br>
                请联系收款方或返回重新扫码。
            </div>
            <a href="javascript:history.back();" class="back-btn">返回上一页</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($project['merchant_name']) ?> - 查询账单</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <style>
        body {background: #eef4fb;font-family: 'Segoe UI','PingFang SC','Arial',sans-serif;margin:0;padding:0;min-height:100vh;}
        .pay-container {width:100%;max-width:430px;margin:60px auto 0 auto;background:#fff;border-radius:28px;box-shadow:0 8px 36px rgba(44,104,255,0.10);padding:38px 30px 24px 30px;}
        .merchant-logo {width:54px;height:54px;border-radius:16px;background:#e9f2ff;display:flex;align-items:center;justify-content:center;margin:0 auto 13px auto;font-size:24px;color:#269fff;}
        .merchant-title {font-size:23px;font-weight:600;color:#2d8cf0;text-align:center;margin-bottom:2px;}
        .project-code {color:#939393;font-size:16px;text-align:center;margin-bottom:2px;}
        .project-desc {color:#a7a7a7;font-size:15px;text-align:center;margin-bottom:16px;}
        label {font-size:17px;display:block;margin-bottom:10px;text-align:left;}
        .input {width:100%;font-size:18px;padding:13px 12px;border-radius:10px;border:1.2px solid #b3cdfa;background:#f7faff;text-align:left;outline:none;transition:border 0.18s;box-sizing:border-box;margin-bottom:22px;}
        .input:focus {border: 1.2px solid #2d8cf0;background: #fff;}
        .btn {width:100%;font-size:19px;font-weight:500;padding:13px 0;border:none;border-radius:12px;margin-top:14px;color:#fff;background:linear-gradient(90deg,#1677ff 30%,#269fff 100%);cursor:pointer;}
        .btn:active {background:#135edb;}
        .pay-footer {margin-top:32px;color:#b5b5b5;font-size:13px;letter-spacing:1px;text-align:center;}
        @media (max-width:500px) {.pay-container{margin:18px auto 0 auto;padding:15px 3vw 12px 3vw;}}
    </style>
</head>
<body>
    <div class="pay-container">
        <div class="merchant-logo"><?= mb_substr($project['merchant_name'] ?? '账', 0, 1, 'utf-8') ?></div>
        <div class="merchant-title"><?= htmlspecialchars($project['merchant_name']) ?></div>
        <div class="project-code"><?= htmlspecialchars($project['name']) ?></div>
        <?php if($project['desc']) { ?>
            <div class="project-desc"><?= htmlspecialchars($project['desc']) ?></div>
        <?php } ?>
        <form action="pay_search.php" method="get" style="width:100%;">
            <input type="hidden" name="code" value="<?= htmlspecialchars($code) ?>">
            <label>请输入客户标识、手机号或姓名：</label>
            <input class="input" type="text" name="keyword" required maxlength="64" placeholder="如房号/学号/手机号/姓名">
            <button class="btn" type="submit">查询账单</button>
        </form>
        <div class="pay-footer">&copy; <?=date('Y')?> 助理Pro | 账单收款</div>
    </div>
</body>
</html>