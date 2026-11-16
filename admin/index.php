<?php
require_once __DIR__.'/functions.php';
$page_title = '后台管理中心';
require_once 'header.php';
require_admin_login();
$admin = function_exists('current_admin') ? current_admin() : admin_current_user();
?>
<style>
.admin-center-main {
    max-width: 900px;
    margin: 48px auto;
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 8px 36px rgba(44,104,255,.09);
    padding: 0;
    overflow: hidden;
}
.admin-header {
    background: linear-gradient(90deg, #3183f4 0%, #53b8f3 100%);
    color: #fff;
    padding: 38px 40px 26px 40px;
    display: flex;
    align-items: center;
    gap: 0 32px;
}
.admin-info {
    flex: 1;
}
.admin-info .uname {
    font-size: 26px;
    font-weight: 600;
    letter-spacing: 1px;
}
.admin-info .uemail {
    color: #e0e8fc;
    font-size: 16px;
    margin-top: 6px;
}
.admin-header-actions {
    margin-left: auto;
    min-width: 120px;
    text-align: right;
}
.admin-header-actions a {
    color: #fff;
    font-size: 15px;
    margin-left: 18px;
    text-decoration: underline;
}
@media (max-width: 700px) {
    .admin-center-main { margin: 0; border-radius: 0; }
    .admin-header { flex-direction: column; align-items: flex-start; gap:12px; padding:28px 8vw 18px 8vw;}
    .admin-header-actions { text-align: left; margin: 0; min-width: inherit;}
}
.ac-grid {
    display: flex;
    flex-wrap: wrap;
    padding: 18px 40px 22px 40px;
    gap: 28px;
    background: #fff;
}
.ac-grid-section {
    flex: 1 1 240px;
    min-width: 220px;
    margin-bottom: 16px;
}
.ac-grid-section h3 {
    font-size: 17px;
    color: #3183f4;
    margin: 0 0 8px 0;
    font-weight: 600;
}
.ac-menu-list {
    list-style: none;
    padding: 0;
    margin: 0;
}
.ac-menu-list li {
    margin-bottom: 7px;
    font-size: 15px;
}
.ac-menu-list a {
    color: #246acc;
    text-decoration: none;
    transition: color .15s;
}
.ac-menu-list a:hover {
    color: #2d8cf0;
    text-decoration: underline;
}
.ac-menu-list .desc {
    color: #999;
    font-size: 13px;
    margin-left: 8px;
}
.ac-footnote {
    padding: 10px 40px 20px 40px;
    color: #999;
    font-size: 14px;
    border-top: 1px solid #f0f3fa;
    background: #fafbff;
}
@media (max-width: 700px) {
    .ac-grid {padding:14px 4vw;}
    .ac-footnote {padding: 8px 4vw 12px 4vw;}
}
</style>
<div class="admin-center-main">
    <div class="admin-header">
        <div class="admin-info">
            <div class="uname"><?=admin_esc($admin['username'])?></div>
            <?php if(!empty($admin['email'])): ?>
                <div class="uemail"><?=admin_esc($admin['email'])?></div>
            <?php endif; ?>
        </div>
        <div class="admin-header-actions">
            <a href="profile.php">个人资料</a>
            <a href="logout.php" style="color:#ffd4d4;">安全退出</a>
        </div>
    </div>
    <div class="ac-grid">
        <div class="ac-grid-section">
            <h3>用户管理</h3>
            <ul class="ac-menu-list">
                <li><a href="users.php">全部用户</a> <span class="desc">管理所有平台用户</span></li>
                <li><a href="users_add.php">新增用户</a> <span class="desc">后台添加新用户</span></li>
            </ul>
        </div>
        <div class="ac-grid-section">
            <h3>商户管理</h3>
            <ul class="ac-menu-list">
                <li><a href="merchants.php">全部商户</a> <span class="desc">管理所有商户信息</span></li>
                <li><a href="merchants_add.php">新增商户</a> <span class="desc">后台添加新商户</span></li>
            </ul>
        </div>
        <div class="ac-grid-section">
            <h3>表单收款</h3>
            <ul class="ac-menu-list">
                <li><a href="forms.php">全部表单</a> <span class="desc">自定义收款表单管理</span></li>
                <li><a href="forms_orders.php">订单管理</a> <span class="desc">查看所有表单订单</span></li>
            </ul>
        </div>
        <div class="ac-grid-section">
            <h3>支付通道</h3>
            <ul class="ac-menu-list">
                <li><a href="commonpay.php">通道管理</a> <span class="desc">所有支付通道配置</span></li>
                <li><a href="commonpay_create.php">新增通道</a> <span class="desc">后台添加新通道</span></li>
            </ul>
        </div>
        <div class="ac-grid-section">
            <h3>账单收款</h3>
            <ul class="ac-menu-list">
                <li><a href="bills.php">全部账单</a> <span class="desc">管理所有账单项目</span></li>
                <li><a href="bills_orders.php">账单订单</a> <span class="desc">所有账单订单管理</span></li>
            </ul>
        </div>
        <div class="ac-grid-section">
            <h3>系统管理</h3>
            <ul class="ac-menu-list">
                <li><a href="admins.php">后台账号管理</a> <span class="desc">添加/管理后台管理员</span></li>
                <li><a href="settings.php">系统设置</a> <span class="desc">基础参数配置</span></li>
            </ul>
        </div>
    </div>
    <div class="ac-footnote">
        <b>后台公告：</b> 管理员如有疑问请联系超级管理员。
    </div>
</div>
<?php require_once 'footer.php'; ?>