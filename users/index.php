<?php
$page_title = '用户中心';
require_once '../header.php';
require_login();
$user = current_user();
?>
<style>
.user-center-main {
    max-width: 900px;
    margin: 48px auto;
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 8px 36px rgba(44,104,255,.09);
    padding: 0;
    overflow: hidden;
}
.user-header {
    background: linear-gradient(90deg, #3183f4 0%, #53b8f3 100%);
    color: #fff;
    padding: 38px 40px 26px 40px;
    display: flex;
    align-items: center;
    gap: 0 32px;
}
.user-info {
    flex: 1;
}
.user-info .uname {
    font-size: 26px;
    font-weight: 600;
    letter-spacing: 1px;
}
.user-info .uemail {
    color: #e0e8fc;
    font-size: 16px;
    margin-top: 6px;
}
.user-header-actions {
    margin-left: auto;
    min-width: 120px;
    text-align: right;
}
.user-header-actions a {
    color: #fff;
    font-size: 15px;
    margin-left: 18px;
    text-decoration: underline;
}
@media (max-width: 700px) {
    .user-center-main { margin: 0; border-radius: 0; }
    .user-header { flex-direction: column; align-items: flex-start; gap:12px; padding:28px 8vw 18px 8vw;}
    .user-header-actions { text-align: left; margin: 0; min-width: inherit;}
}
.uc-grid {
    display: flex;
    flex-wrap: wrap;
    padding: 18px 40px 22px 40px;
    gap: 28px;
    background: #fff;
}
.uc-grid-section {
    flex: 1 1 240px;
    min-width: 220px;
    margin-bottom: 16px;
}
.uc-grid-section h3 {
    font-size: 17px;
    color: #3183f4;
    margin: 0 0 8px 0;
    font-weight: 600;
}
.uc-menu-list {
    list-style: none;
    padding: 0;
    margin: 0;
}
.uc-menu-list li {
    margin-bottom: 7px;
    font-size: 15px;
}
.uc-menu-list a {
    color: #246acc;
    text-decoration: none;
    transition: color .15s;
}
.uc-menu-list a:hover {
    color: #2d8cf0;
    text-decoration: underline;
}
.uc-menu-list .desc {
    color: #999;
    font-size: 13px;
    margin-left: 8px;
}
.uc-footnote {
    padding: 10px 40px 20px 40px;
    color: #999;
    font-size: 14px;
    border-top: 1px solid #f0f3fa;
    background: #fafbff;
}
@media (max-width: 700px) {
    .uc-grid {padding:14px 4vw;}
    .uc-footnote {padding: 8px 4vw 12px 4vw;}
}
</style>
<div class="user-center-main">
    <div class="user-header">
        <div class="user-info">
            <div class="uname"><?=esc($user['username'])?></div>
            <?php if(!empty($user['email'])): ?>
                <div class="uemail"><?=esc($user['email'])?></div>
            <?php endif; ?>
            <?php if(!empty($user['mobile'])): ?>
                <div style="color:#e0e8fc;font-size:15px;margin-top:4px;"><?=esc($user['mobile'])?></div>
            <?php endif; ?>
        </div>
        <div class="user-header-actions">
            <a href="/users/profile.php">个人资料</a>
            <a href="/users/logout.php" style="color:#ffd4d4;">安全退出</a>
        </div>
    </div>
    <div class="uc-grid">
        <div class="uc-grid-section">
            <h3>收款码收款</h3>
            <ul class="uc-menu-list">
                <li><a href="/codes/index.php">全部收款码</a> <span class="desc">管理和查看所有收款二维码</span></li>
                <li><a href="/codes/create.php">新建收款码</a> <span class="desc">生成新的收款二维码</span></li>
                <li><a href="/codes/orders.php">订单查询</a> <span class="desc">查看通过收款码产生的订单</span></li>
            </ul>
        </div>
        <div class="uc-grid-section">
            <h3>表单收款</h3>
            <ul class="uc-menu-list">
                <li><a href="/forms/index.php">全部表单</a> <span class="desc">所有自定义收款表单</span></li>
                <li><a href="/forms/create.php">新建表单</a> <span class="desc">创建新的表单收款入口</span></li>
                <li><a href="/forms/orders.php">订单查询</a> <span class="desc">查看表单收款订单</span></li>
            </ul>
        </div>
        <div class="uc-grid-section">
            <h3>账单收款</h3>
            <ul class="uc-menu-list">
                <li><a href="/bills/index.php">全部项目</a> <span class="desc">管理和查看账单项目</span></li>
                <li><a href="/bills/create.php">新建项目</a> <span class="desc">新建账单收款项目</span></li>
                <li><a href="/bills/orders.php">订单查询</a> <span class="desc">账单项目相关订单</span></li>
            </ul>
        </div>
        <div class="uc-grid-section">
            <h3>商户配置</h3>
            <ul class="uc-menu-list">
                <li><a href="/merchants/index.php">商户管理</a> <span class="desc">添加或管理商户资料</span></li>
                <li><a href="/merchants/create.php">新建商户</a> <span class="desc">新增一个商户账号</span></li>
            </ul>
        </div>
        <div class="uc-grid-section">
            <h3>支付通道</h3>
            <ul class="uc-menu-list">
                <li><a href="/commonpay/index.php">通道配置</a> <span class="desc">管理支付渠道及参数</span></li>
                <li><a href="/commonpay/channel_create.php">新增通道</a> <span class="desc">添加新的支付通道</span></li>
            </ul>
        </div>
        <div class="uc-grid-section">
            <h3>开发与帮助</h3>
            <ul class="uc-menu-list">
                <li><a href="/commonpay/apihelp.php">API开发文档</a> <span class="desc">第三方系统集成参考</span></li>
                <li><a href="/commonpay/help.php">H5支付说明</a> <span class="desc">网页支付/跳转集成说明</span></li>
            </ul>
        </div>
    </div>
    <div class="uc-footnote">
        <b>系统公告：</b> 如需帮助请联系在线客服或管理员。
    </div>
</div>
<?php require_once '../footer.php'; ?>