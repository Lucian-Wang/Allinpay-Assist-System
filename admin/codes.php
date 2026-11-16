<?php
require_once 'header.php';
$status = isset($_GET['status']) ? intval($_GET['status']) : -1; // -1=全部,1=正常,0=禁用
$db = get_admin_db();
if ($status === -1) {
    $stmt = $db->query('SELECT c.*, u.username AS user_name, m.name AS merchant_name FROM codes c LEFT JOIN users u ON c.user_id=u.id LEFT JOIN merchants m ON c.merchant_id=m.id ORDER BY c.id DESC');
} else {
    $stmt = $db->prepare('SELECT c.*, u.username AS user_name, m.name AS merchant_name FROM codes c LEFT JOIN users u ON c.user_id=u.id LEFT JOIN merchants m ON c.merchant_id=m.id WHERE c.status=? ORDER BY c.id DESC');
    $stmt->execute([$status]);
}
$codes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<style>
.code-card {
    max-width: 1100px;
    margin: 48px auto;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 32px #0001;
    padding: 32px 32px 18px 32px;
}
.code-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 26px;
}
.code-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #222;
    letter-spacing: 1.5px;
}
.code-actions .button {
    margin-left: 8px;
    border-radius: 6px;
    padding: 7px 20px;
    font-size: 1rem;
    transition: background 0.2s;
}
.code-filters {
    margin-bottom: 24px;
    display: flex;
    gap: 10px;
}
.code-filters .button {
    border-radius: 4px;
    padding: 6px 18px;
    transition: background 0.2s;
    font-size: 0.98rem;
}
.code-filters .button.active {
    background: #409eff;
    color: #fff;
}
.code-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
}
.code-table th, .code-table td {
    padding: 14px 10px;
    text-align: center;
}
.code-table thead th {
    background: #f6f8fa;
    font-weight: 550;
    color: #444;
    border-bottom: 2px solid #eaeaea;
}
.code-table tbody tr:nth-child(odd) {
    background: #fafbfc;
}
.code-table tbody tr:hover {
    background: #e6f7ff;
    transition: background 0.2s;
}
.code-table .op-group {
    display: flex;
    gap: 8px;
    justify-content: center;
}
.code-table .op-group a {
    color: #409eff;
    background: #f2f7fc;
    padding: 3px 14px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 0.96em;
    transition: background .15s, color .15s;
}
.code-table .op-group a:hover {
    background: #409eff;
    color: #fff;
}
@media(max-width:900px){
    .code-card {padding:10px;}
    .code-table th,.code-table td{padding:7px 2px;}
    .code-title{font-size:1.1rem;}
}
</style>
<div class="code-card" style="max-width:1100px;margin:48px auto;">
    <div class="code-head">
        <div class="code-title">收款码管理</div>
        <div class="code-actions">
            <a class="button" href="codes_add.php" style="background:#409eff;color:#fff;">+ 新建收款码</a>
        </div>
    </div>
    <div class="code-filters">
        <a href="codes.php?status=1" class="button<?=$status===1?' active':''?>">正常</a>
        <a href="codes.php?status=0" class="button<?=$status===0?' active':''?>">禁用</a>
        <a href="codes.php?status=-1" class="button<?=$status===-1?' active':''?>">全部</a>
    </div>
    <table class="code-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>归属用户</th>
                <th>名称</th>
                <th>商户</th>
                <th>短码</th>
                <th>状态</th>
                <th>创建时间</th>
                <th style="width:250px;">操作</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($codes)) : ?>
            <tr><td colspan="8" style="color:#888;">暂无收款码，点击右上角“新建收款码”添加。</td></tr>
        <?php else: foreach ($codes as $c): ?>
            <tr>
                <td><?=admin_esc($c['id'])?></td>
                <td><?=admin_esc($c['user_name'])?></td>
                <td><?=admin_esc($c['name'])?></td>
                <td><?=admin_esc($c['merchant_name'])?></td>
                <td>
                    <a href="/codes/pay.php?code=<?=admin_esc($c['code'])?>" target="_blank" style="color:#409eff;">
                        <?=admin_esc($c['code'])?>
                    </a>
                </td>
                <td>
                    <?php if ($c['status']==1): ?><span style="color:green;">正常</span>
                    <?php else: ?><span style="color:#d33;">禁用</span>
                    <?php endif; ?>
                </td>
                <td><?=admin_esc($c['created_at'])?></td>
                <td>
                    <div class="op-group">
                        <a href="codes_orders.php?code=<?=admin_esc($c['code'])?>">订单</a>
                        <a href="codes_edit.php?id=<?=$c['id']?>">编辑</a>
                        <a href="codes_view.php?id=<?=$c['id']?>">详情</a>
                        <?php if ($c['status']==1): ?>
                            <a href="codes_disable.php?id=<?=$c['id']?>" onclick="return confirm('确定禁用？')">禁用</a>
                        <?php else: ?>
                            <a href="codes_enable.php?id=<?=$c['id']?>" onclick="return confirm('确定启用？')">启用</a>
                        <?php endif; ?>
                        <?php
                            $domain = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
                            $link = $domain . "/codes/scan.php?code=" . urlencode($c['code']);
                            $qrcode_img = "/codes/qrcode.php?"
                                . "data=" . urlencode($link)
                                . "&id=" . urlencode($c['code'])
                                . "&company=" . urlencode($c['name']);
                        ?>
                        <a href="<?=$qrcode_img?>" target="_blank">二维码</a>
                        <a href="codes_delete.php?id=<?=$c['id']?>" onclick="return confirm('确定要永久删除？此操作不可恢复！')" style="color:#f56c6c;">删除</a>
                    </div>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?php require_once 'footer.php'; ?>