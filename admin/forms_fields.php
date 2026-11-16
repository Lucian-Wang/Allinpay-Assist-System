<?php
$page_title = '字段管理';
require_once 'header.php';
$db = get_admin_db();
$form_id = intval($_GET['form_id'] ?? 0);

if (!$form_id) exit('参数错误');

// 查表单信息
$stmt = $db->prepare("SELECT f.*, u.username as user_name FROM forms f LEFT JOIN users u ON f.user_id=u.id WHERE f.id=?");
$stmt->execute([$form_id]);
$form = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$form) exit('参数错误');

// 字段列表
$stmt = $db->prepare("SELECT * FROM form_fields WHERE form_id=? ORDER BY sort ASC, id ASC");
$stmt->execute([$form_id]);
$fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="content-card" style="max-width:950px;margin:48px auto;">
    <div class="card-header">
        <div class="card-title">字段管理 - <?=admin_esc($form['name'])?></div>
        <div class="card-actions">
            <a class="button" href="forms_fields_add.php?form_id=<?=$form_id?>">+ 新增字段</a>
            <a class="button" href="forms.php">返回</a>
        </div>
    </div>
    <div style="overflow-x:auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>名称</th>
                    <th>类型</th>
                    <th>选项</th>
                    <th>必填</th>
                    <th>排序</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($fields as $f): ?>
                <tr>
                    <td><?=admin_esc($f['id'])?></td>
                    <td><?=admin_esc($f['label'])?></td>
                    <td><?=admin_esc($f['field_type'])?></td>
                    <td><?=admin_esc($f['options'])?></td>
                    <td><?= $f['required'] ? '是' : '否' ?></td>
                    <td>
                        <?=admin_esc($f['sort'])?>
                        <a class="action-link" href="forms_fields_sort.php?form_id=<?=$form_id?>&id=<?=$f['id']?>&dir=up" title="上移">↑</a>
                        <a class="action-link" href="forms_fields_sort.php?form_id=<?=$form_id?>&id=<?=$f['id']?>&dir=down" title="下移">↓</a>
                    </td>
                    <td>
                        <a href="forms_fields_edit.php?id=<?=$f['id']?>" class="action-link">编辑</a>
                        <a href="forms_fields_delete.php?id=<?=$f['id']?>&form_id=<?=$form_id?>" class="action-link" onclick="return confirm('确定删除?');">删除</a>
                    </td>
                </tr>
            <?php endforeach;?>
            <?php if (empty($fields)): ?>
                <tr><td colspan="7" style="color:#888;text-align:center;">暂无字段</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once 'footer.php'; ?>