<?php
$page_title = '助理Pro-Zhuli.Pro-聚合支付平台';
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title><?=htmlspecialchars($page_title)?></title>
    <meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1">
    <style>
        body {
            background: #f7fafd;
            font-family: "PingFang SC", "Microsoft Yahei", "Helvetica Neue", Arial, "Hiragino Sans GB", "Heiti SC", sans-serif;
            color: #222;
            margin: 0;
            padding: 0;
        }
        .landing-main {
            max-width: 890px;
            margin: 54px auto 0 auto;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 8px 36px rgba(44,104,255,.10);
            padding: 0 0 38px 0;
            overflow: hidden;
        }
        .landing-header {
            background: linear-gradient(90deg, #3183f4 0%, #53b8f3 100%);
            color: #fff;
            padding: 52px 40px 38px 40px;
            text-align: center;
        }
        .landing-header h1 {
            font-size: 34px;
            letter-spacing: 2px;
            font-weight: bold;
            margin: 0 0 14px 0;
        }
        .landing-header .desc {
            font-size: 18px;
            margin-top: 0;
            color: #e4f0ff;
        }
        .landing-features {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-gap: 30px 28px;
            padding: 36px 48px 0 48px;
            justify-items: stretch;
        }
        .feature-card {
            background: #f8fbff;
            border-radius: 10px;
            padding: 24px 18px 12px 22px;
            box-shadow: 0 2px 8px #e7f0ff80;
            min-height: 110px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }
        .feature-card h3 {
            font-size: 17px;
            margin: 0 0 8px 0;
            color: #3183f4;
            font-weight: bold;
        }
        .feature-card p {
            color: #555;
            font-size: 15px;
            margin: 0;
            line-height: 1.7;
        }
        .landing-actions {
            text-align: center;
            padding: 36px 0 0 0;
        }
        .landing-actions a {
            display: inline-block;
            background: #3183f4;
            color: #fff;
            font-size: 18px;
            padding: 12px 38px;
            border-radius: 28px;
            text-decoration: none;
            margin: 0 14px;
            box-shadow: 0 2px 8px #3183f41c;
            transition: background 0.2s;
        }
        .landing-actions a:hover {
            background: #246acc;
        }
        .landing-footer {
            padding: 22px 40px 0 40px;
            text-align: center;
            color: #999;
            font-size: 14px;
        }
        @media (max-width: 900px) {
            .landing-main { max-width: 99vw; }
            .landing-header { padding:30px 2vw 22px 2vw;}
            .landing-features { padding: 18px 2vw 0 2vw; grid-gap:18px 0;}
            .feature-card { min-width: 0; }
        }
        @media (max-width: 700px) {
            .landing-main { border-radius: 0; padding-bottom: 18px;}
            .landing-features { 
                grid-template-columns: 1fr;
                padding: 12px 3vw 0 3vw; 
            }
            .feature-card { min-width: 99vw; max-width: 98vw;}
            .landing-footer { padding: 12px 4vw 0 4vw;}
        }
    </style>
</head>
<body>
<div class="landing-main">
    <div class="landing-header">
        <h1>聚合支付平台</h1>
        <div class="desc">一站式收款 · 多通道管理 · 便捷高效 · 安全合规</div>
    </div>
    <div class="landing-features">
        <div class="feature-card">
            <h3>多通道聚合</h3>
            <p>支持支付宝、微信、银行卡等主流支付方式，灵活配置通道，统一对账和管理。</p>
        </div>
        <div class="feature-card">
            <h3>多样收款入口</h3>
            <p>支持收款码、表单收款、账单收款，满足多场景下的收款需求。</p>
        </div>
        <div class="feature-card">
            <h3>开放API接口</h3>
            <p>提供完善的API文档，方便第三方系统快速集成与自动化收款。</p>
        </div>
        <div class="feature-card">
            <h3>数据统计与对账</h3>
            <p>订单明细、对账单一站式管理，支持导出和多维度分析。</p>
        </div>
        <div class="feature-card">
            <h3>安全合规</h3>
            <p>数据加密存储，权限分级，合规保障您的资金安全。</p>
        </div>
        <div class="feature-card">
            <h3>灵活扩展</h3>
            <p>支持多商户、多场景接入，平台功能可按需扩展，满足企业成长需求。</p>
        </div>
    </div>
    <div class="landing-actions">
        <a href="/users/login.php">登录账户</a>
        <a href="/users/register.php" style="background:#53b8f3;">免费注册</a>
    </div>
    <div class="landing-footer">
        如需对接或有疑问，请联系管理员。<br>
        &copy; <?=date('Y')?> 助理Pro | 基于通联支付构建的第三方支付平台 All Rights Reserved.
    </div>
</div>
</body>
</html>