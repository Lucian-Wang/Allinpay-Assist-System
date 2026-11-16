<?php
require_once '../header.php';
require_login();
$user = current_user();
$db = get_db();

// 调试SQL错误输出（防止prepare为false时致命报错）
$stmt = $db->prepare("SELECT f.*, m.name as merchant_name FROM forms f LEFT JOIN merchants m ON f.merchant_id=m.id WHERE f.user_id=? AND f.status=1 ORDER BY f.id DESC");
if (!$stmt) {
    die('SQL ERROR: ' . $db->error); // 输出SQL错误信息，便于排查
}
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$forms = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<div class="content-card" style="max-width:900px;margin:48px auto;">
    <div class="card-header" style="margin-bottom:20px;">
        <div class="card-title">表单收款</div>
        <div class="card-actions">
            <a class="button" href="create.php">+ 新建表单</a>
            <a class="button" href="orders.php">全部订单</a>
        </div>
    </div>
    <table class="table" style="width:100%;background:#fff;">
        <thead>
            <tr>
                <th>ID</th>
                <th>表单名称</th>
                <th>绑定商户</th>
                <th>短码</th>
                <th style="width:260px;">操作</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($forms as $f): ?>
            <tr>
                <td><?=esc($f['id'])?></td>
                <td><?=esc($f['name'])?></td>
                <td><?=esc($f['merchant_name'])?></td>
                <td>
                    <?php if (!empty($f['code'])): ?>
                        <a href="pay.php?code=<?=urlencode($f['code'])?>" target="_blank"><?=esc($f['code'])?></a>
                    <?php else: ?>
                        <span style="color:#999;">--</span>
                        <span style="color:#ccc;">|</span>
                        <a href="pay.php?form_id=<?=esc($f['id'])?>" target="_blank"><?=esc($f['code'])?></a>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="edit.php?id=<?=$f['id']?>">编辑</a>
                    <span style="color:#ccc;">|</span>
                    <a href="fields.php?form_id=<?=$f['id']?>">字段</a>
                    <span style="color:#ccc;">|</span>
                    <a href="orders.php?code=<?=$f['code']?>">订单</a>
                    <span style="color:#ccc;">|</span>
                    <?php
                        $domain = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
                        $link = $domain . "/forms/pay.php?" . (!empty($f['code']) ? ("code=" . urlencode($f['code'])) : ("form_id=" . urlencode($f['id'])));
                        $qrcode_img = "/codes/qrcode.php?"
                            . "data=" . urlencode($link)
                            . "&id=" . urlencode(!empty($f['code']) ? $f['code'] : $f['id'])
                            . "&company=" . urlencode($f['name']);
                    ?>
                    <a href="<?=$qrcode_img?>" target="_blank">二维码</a>
                    <span style="color:#ccc;">|</span>
                    <a href="delete.php?id=<?=$f['id']?>" onclick="return confirm('确定要删除吗？');" style="color:#f56c6c;">删除</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once '../footer.php'; ?>