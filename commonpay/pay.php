<?php
require_once '../db.php';

// 获取参数
$channel      = $_GET['channel'] ?? '';
$pay_order_no = $_GET['pay_order_no'] ?? '';
$amount       = $_GET['amount'] ?? '';
$subject      = $_GET['subject'] ?? '';
$user_id      = $_GET['user_id'] ?? '';
$biz_sys      = $_GET['biz_sys'] ?? '';
$biz_order_no = $_GET['biz_order_no'] ?? '';
$notify_url   = $_GET['notify_url'] ?? '';
$return_url   = $_GET['return_url'] ?? '';
$extra        = $_GET['extra'] ?? '';

// 校验参数（只校验 channel/pay_order_no/amount/subject 必填）
if (!$channel || !$pay_order_no || !$amount || !$subject) {
    show_error('参数不完整，请联系支付方获取正确的收款链接。');
}
if (!is_numeric($amount) || $amount < 0.01) {
    show_error('金额错误，请检查链接或联系支付方。');
}

// 查找渠道和商户
$db = get_db();
$sql = "SELECT c.*, m.name AS merchant_name FROM commonpay_channels c LEFT JOIN merchants m ON c.merchant_id=m.id WHERE c.channel=? AND c.status=1";
$stmt = $db->prepare($sql);
if (!$stmt) show_error('系统异常，请稍后再试。');
$stmt->bind_param('s', $channel);
$stmt->execute();
$channelInfo = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$channelInfo) {
    show_error('支付通道不存在或已禁用，请联系支付方。');
}

// 检查订单是否已存在
$sql = "SELECT status FROM commonpay_orders WHERE pay_order_no=?";
$stmt = $db->prepare($sql);
if (!$stmt) show_error('系统异常，请稍后再试。');
$stmt->bind_param('s', $pay_order_no);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($order && $order['status'] === 'paid') {
    header("Location: pay_result.php?pay_order_no=" . urlencode($pay_order_no));
    exit;
}

// 只写一次订单
if (!$order) {
    $sql = "INSERT INTO commonpay_orders 
        (channel_id, merchant_id, pay_order_no, amount, pay_channel, status, subject, notify_url, return_url, user_id, biz_sys, biz_order_no, extra, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
    $stmt = $db->prepare($sql);
    if (!$stmt) show_error('系统异常，请稍后再试。');
    $pay_channel = $channelInfo['channel'];
    $stmt->bind_param(
        'iisdssssssss',
        $channelInfo['id'],
        $channelInfo['merchant_id'],
        $pay_order_no,
        $amount,
        $pay_channel,
        $subject,
        $notify_url,
        $return_url,
        $user_id,
        $biz_sys,
        $biz_order_no,
        $extra
    );
    $stmt->execute();
    $stmt->close();
}

// 客户端环境识别
function get_pay_env() {
    $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    if (strpos($ua, 'micromessenger') !== false) {
        return 'wechat';
    } elseif (strpos($ua, 'alipayclient') !== false) {
        return 'alipay';
    } else {
        return 'pc';
    }
}
$env = get_pay_env();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($channelInfo['merchant_name'] ?? '收银台') ?> - 支付信息确认</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <!-- 样式同你原来 -->
    <style>
        body {
            background: linear-gradient(135deg, #eaf2fe 0%, #f5f7fa 100%);
            font-family: 'Segoe UI', 'PingFang SC', 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        .pay-container {
            width: 100%;
            max-width: 370px;
            margin: 60px auto 0 auto;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 8px 36px rgba(44,104,255,0.10);
            padding: 36px 24px 18px 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-sizing: border-box;
        }
        .merchant-logo {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, #e9f2ff 0%, #d6e3fd 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
            font-size: 26px;
            color: #2d8cf0;
            font-weight: bold;
            letter-spacing: 2px;
            box-shadow: 0 4px 16px rgba(45,140,240,0.04);
        }
        .merchant-title {
            font-size: 20px;
            font-weight: 600;
            color: #2d8cf0;
            margin-bottom: 3px;
            text-align: center;
        }
        .pos-name {
            color: #999;
            font-size: 14px;
            margin-bottom: 18px;
            text-align: center;
        }
        .order-info {
            background: #f6f8fb;
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 18px;
            color: #444;
            width: 100%;
            font-size: 15px;
            box-sizing: border-box;
        }
        .amount-label {
            font-size: 15px;
            color: #3a3a3a;
            margin-bottom: 8px;
            text-align: left;
            width: 100%;
        }
        .amount-input-wrap {
            width: 100%;
            position: relative;
            margin-bottom: 16px;
        }
        .amount-input {
            width: 100%;
            font-size: 22px;
            padding: 13px 12px;
            border-radius: 8px;
            border: 1.2px solid #b3cdfa;
            background: #f7faff;
            text-align: center;
            outline: none;
            transition: border 0.18s;
            box-shadow: 0 1px 4px rgba(45,140,240,0.05);
            box-sizing: border-box;
        }
        .amount-input:focus {
            border: 1.2px solid #2d8cf0;
            background: #fff;
        }
        .btn-row {
            display: flex;
            justify-content: center;
            gap: 14px;
            width: 100%;
            margin-top: 4px;
        }
        .pay-btn {
            flex: 1;
            font-size: 17px;
            font-weight: 500;
            padding: 12px 0;
            border: none;
            border-radius: 8px;
            color: #fff;
            cursor: pointer;
            transition: background 0.18s, box-shadow 0.15s;
            box-shadow: 0 2px 8px rgba(44,104,255,0.06);
        }
        .pay-btn.wx {
            background: linear-gradient(90deg, #21c08b 30%, #16cfa8 100%);
        }
        .pay-btn.wx:active {
            background: #179e72;
        }
        .pay-btn.ali {
            background: linear-gradient(90deg, #1677ff 30%, #269fff 100%);
        }
        .pay-btn.ali:active {
            background: #135edb;
        }
        .pay-footer {
            margin-top: 26px;
            color: #b5b5b5;
            font-size: 12px;
            letter-spacing: 1px;
            text-align: center;
        }
        @media (max-width: 500px) {
            .pay-container {
                margin: 18px auto 0 auto;
                padding: 15px 3vw 12px 3vw;
            }
            .amount-input {
                font-size: 17px;
            }
            .merchant-logo {
                width: 36px; height: 36px; font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="pay-container">
        <div class="merchant-logo">
            <?= mb_substr($channelInfo['merchant_name'] ?? '商', 0, 1, 'utf-8') ?>
        </div>
        <div class="merchant-title"><?= htmlspecialchars($channelInfo['merchant_name']) ?></div>
        <div class="pos-name"><?= htmlspecialchars($channelInfo['name'] ?? '') ?></div>
        <div class="order-info">
            金额：<?=number_format($amount,2)?> 元<br>
            订单号：<?=htmlspecialchars($pay_order_no)?><br>
            商品名：<?=htmlspecialchars($subject)?><br>
            <?php if ($user_id): ?>
            用户ID：<?=htmlspecialchars($user_id)?><br>
            <?php endif; ?>
            <?php if ($biz_sys): ?>
            业务系统：<?=htmlspecialchars($biz_sys)?><br>
            <?php endif; ?>
            <?php if ($biz_order_no): ?>
            业务订单号：<?=htmlspecialchars($biz_order_no)?><br>
            <?php endif; ?>
            <?php if ($extra): ?>
            扩展参数：<?=htmlspecialchars($extra)?><br>
            <?php endif; ?>
        </div>
        <form action="pay_action.php" method="post" id="payform" style="width:100%;">
            <!-- 这些参数全部带过去 -->
            <input type="hidden" name="channel" value="<?= htmlspecialchars($channel) ?>">
            <input type="hidden" name="pay_order_no" value="<?= htmlspecialchars($pay_order_no) ?>">
            <input type="hidden" name="subject" value="<?= htmlspecialchars($subject) ?>">
            <input type="hidden" name="user_id" value="<?= htmlspecialchars($user_id) ?>">
            <input type="hidden" name="biz_sys" value="<?= htmlspecialchars($biz_sys) ?>">
            <input type="hidden" name="biz_order_no" value="<?= htmlspecialchars($biz_order_no) ?>">
            <input type="hidden" name="notify_url" value="<?= htmlspecialchars($notify_url) ?>">
            <input type="hidden" name="return_url" value="<?= htmlspecialchars($return_url) ?>">
            <input type="hidden" name="extra" value="<?= htmlspecialchars($extra) ?>">
            <input type="hidden" name="amount" value="<?= htmlspecialchars($amount) ?>">
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
        <div class="pay-footer">
           &copy; <?=date('Y')?> 助理Pro | 基于通联支付构建的第三方支付平台
        </div>
    </div>
</body>
</html>
<?php
function show_error($msg) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>收款异常</title>
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
        <style>
            body {background: linear-gradient(135deg, #eaf2fe 0%, #f5f7fa 100%);font-family: 'Segoe UI', 'PingFang SC', 'Arial', sans-serif;margin: 0;padding: 0;min-height: 100vh;}
            .error-container {width: 100%;max-width: 400px;margin: 100px auto 0 auto;background: #fff;border-radius: 18px;box-shadow: 0 8px 36px rgba(44, 104, 255, 0.10);padding: 36px 24px 32px 24px;display: flex;flex-direction: column;align-items: center;box-sizing: border-box;text-align: center;}
            .error-icon {font-size: 54px;color: #ff9800;margin-bottom: 18px;}
            .error-title {font-size: 22px;color: #f44336;font-weight: bold;margin-bottom: 10px;}
            .error-message {font-size: 16px;color: #666;margin-bottom: 22px;}
            .back-btn {display: inline-block;padding: 10px 32px;background: linear-gradient(90deg, #1677ff 30%, #269fff 100%);color: #fff;border-radius: 8px;text-decoration: none;font-size: 16px;transition: background 0.18s;margin-top: 10px;}
            .back-btn:hover { background: #135edb; }
            @media (max-width: 500px) {.error-container {margin: 28px auto 0 auto;padding: 18px 3vw 18px 3vw;}}
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">⚠️</div>
            <div class="error-title">收款异常</div>
            <div class="error-message">
                <?= htmlspecialchars($msg) ?>
            </div>
            <a href="javascript:history.back();" class="back-btn">返回上一页</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>