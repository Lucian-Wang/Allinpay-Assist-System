<?php
require_once '../db.php';
$code = $_GET['code'] ?? '';
if (!$code) exit('参数错误');

// 查找收款码信息（只查未禁用的）
$db = get_db();
$sql = "SELECT c.*, m.name AS merchant_name FROM codes c LEFT JOIN merchants m ON c.merchant_id=m.id WHERE c.code=? AND c.status=1";
$stmt = $db->prepare($sql);
$stmt->bind_param('s', $code);
$stmt->execute();
$codeInfo = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$codeInfo) {
    // 友好提示页面
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>收款码不可用</title>
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
        <style>
            body {
                background: linear-gradient(135deg, #eaf2fe 0%, #f5f7fa 100%);
                font-family: 'Segoe UI', 'PingFang SC', 'Arial', sans-serif;
                margin: 0;
                padding: 0;
                min-height: 100vh;
            }
            .error-container {
                width: 100%;
                max-width: 400px;
                margin: 100px auto 0 auto;
                background: #fff;
                border-radius: 18px;
                box-shadow: 0 8px 36px rgba(44, 104, 255, 0.10);
                padding: 36px 24px 32px 24px;
                display: flex;
                flex-direction: column;
                align-items: center;
                box-sizing: border-box;
                text-align: center;
            }
            .error-icon {
                font-size: 54px;
                color: #ff9800;
                margin-bottom: 18px;
            }
            .error-title {
                font-size: 22px;
                color: #f44336;
                font-weight: bold;
                margin-bottom: 10px;
            }
            .error-message {
                font-size: 16px;
                color: #666;
                margin-bottom: 22px;
            }
            .back-btn {
                display: inline-block;
                padding: 10px 32px;
                background: linear-gradient(90deg, #1677ff 30%, #269fff 100%);
                color: #fff;
                border-radius: 8px;
                text-decoration: none;
                font-size: 16px;
                transition: background 0.18s;
                margin-top: 10px;
            }
            .back-btn:hover { background: #135edb; }
            @media (max-width: 500px) {
                .error-container {
                    margin: 28px auto 0 auto;
                    padding: 18px 3vw 18px 3vw;
                }
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">⚠️</div>
            <div class="error-title">收款码不可用</div>
            <div class="error-message">
                抱歉，该收款码不存在或已被禁用。<br>
                请联系收款方或返回重新扫码。
            </div>
            <a href="javascript:history.back();" class="back-btn">返回上一页</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// 生成订单号（核心！）
$order_no = 'C' . date('YmdHis') . rand(1000,9999);

// 客户端识别
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
    <title><?= $codeInfo['merchant_name'] ?? '收银台' ?> - 输入金额付款</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
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
            box-shadow: 0 8px 36px rgba(44, 104, 255, 0.10);
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
            <?= mb_substr($codeInfo['merchant_name'] ?? '商', 0, 1, 'utf-8') ?>
        </div>
        <div class="merchant-title"><?= htmlspecialchars($codeInfo['merchant_name']) ?></div>
        <div class="pos-name"><?= htmlspecialchars($codeInfo['name']) ?></div>
        <form action="pay_action.php" method="post" id="payform" style="width:100%;">
            <input type="hidden" name="code" value="<?= htmlspecialchars($code) ?>">
            <input type="hidden" name="order_no" value="<?= htmlspecialchars($order_no) ?>">
            <div class="amount-label">请输入付款金额</div>
            <div class="amount-input-wrap">
                <input class="amount-input" type="number" name="amount" min="0.01" step="0.01" required placeholder="请输入金额（元）">
            </div>
            <div class="amount-label">备注（可选）</div>
            <div class="amount-input-wrap">
                <input class="amount-input" type="text" name="remark" maxlength="60" placeholder="请输入备注">
            </div>
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