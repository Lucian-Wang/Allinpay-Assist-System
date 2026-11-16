<?php
$page_title = '我的商户配置';
require_once '../header.php';
require_login();
$user = current_user();
$db = get_db();
$stmt = $db->prepare("SELECT * FROM merchants WHERE user_id=? ORDER BY id DESC");
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<div class="content-card" style="max-width:900px;margin:48px auto;">
    <div class="card-header">
        <div class="card-title">我的商户配置</div>
        <div class="card-actions">
            <a href="/users/index.php">返回用户中心</a>
            <a href="/users/logout.php">退出</a>
        </div>
    </div>
    <a class="button" href="create.php">+ 新建商户</a>
    <div style="overflow-x:auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>名称</th>
                    <th>支付通道</th>
                    <th>AppID</th>
                    <th>CUSID</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($list as $row): ?>
                <tr>
                    <td><?=esc($row['id'])?></td>
                    <td><?=esc($row['name'])?></td>
                    <td><?=esc($row['pay_type'])?></td>
                    <td><?=esc($row['appid'])?></td>
                    <td><?=esc($row['cusid'])?></td>
                    <td class="actions">
                        <a href="view.php?id=<?=$row['id']?>">查看</a>
                        <a href="edit.php?id=<?=$row['id']?>">编辑</a>
                        <a href="delete.php?id=<?=$row['id']?>" onclick="return confirm('确定删除？')">删除</a>
                    </td>
                </tr>
            <?php endforeach;?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once '../footer.php'; ?>