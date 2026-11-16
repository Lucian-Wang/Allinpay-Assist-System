<?php
$page_title = '收款码卡台';
require_once '../header.php';
require_login();
$user = current_user();
$db = get_db();
$stmt = $db->prepare("SELECT c.*, m.name AS merchant_name FROM codes c LEFT JOIN merchants m ON c.merchant_id=m.id WHERE c.user_id=? AND c.status=1 ORDER BY c.id DESC");
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$codes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<div class="content-card" style="max-width:900px;margin:48px auto;">
    <div class="card-header" style="margin-bottom:20px;">
        <div class="card-title">收款码卡台</div>
        <div class="card-actions">
            <a class="button" href="create.php">+ 新建收款码</a> <a class="button" href="orders.php">全部订单</a>
        </div>
    </div>
    <table class="table" style="width:100%;background:#fff;">
        <thead>
            <tr>
                <th style="width:60px;">ID</th>
                <th>名称</th>
                <th>绑定商户</th>
                <th>短码</th>
                <th style="width:220px;">操作</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($codes as $c): ?>
            <tr>
                <td><?=esc($c['id'])?></td>
                <td><?=esc($c['name'])?></td>
                <td><?=esc($c['merchant_name'])?></td>
                <td><a href="https://allinpay.zhuli.pro/codes/pay.php?code=<?=esc($c['code'])?>" target="_blank"><?=esc($c['code'])?></a></td>
                <td>
                    <a href="edit.php?id=<?=$c['id']?>">编辑</a>
                    <span style="color:#ccc;">|</span>
                    <a href="orders.php?code=<?=urlencode($c['code'])?>">订单</a>
                    <span style="color:#ccc;">|</span>
                    <?php
    $domain = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
    $link = $domain . "/codes/scan.php?code=" . urlencode($c['code']);
    $qrcode_img = "/codes/qrcode.php?"
        . "data=" . urlencode($link)
        . "&id=" . urlencode($c['code'])
        . "&company=" . urlencode($c['name']);
?>
<a href="<?=$qrcode_img?>" target="_blank">二维码</a>
                    <span style="color:#ccc;">|</span>
                    <a href="delete.php?id=<?=$c['id']?>" onclick="return confirm('确定要删除吗？');" style="color:#f56c6c;">删除</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once '../footer.php'; ?>