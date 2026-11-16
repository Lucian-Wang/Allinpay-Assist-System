<?php
$page_title = '编辑字段';
require_once 'header.php';
$db = get_admin_db();
$id = intval($_GET['id'] ?? 0);
$msg = '';
$stmt = $db->prepare("SELECT * FROM form_fields WHERE id=?");
$stmt->execute([$id]);
$field = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$field) exit('参数错误');
$form_id = $field['form_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $label = trim($_POST['label'] ?? '');
    $type = $_POST['field_type'] ?? 'text';
    $options = trim($_POST['options'] ?? '');
    $required = intval($_POST['required'] ?? 0);
    $sort = intval($_POST['sort'] ?? 0);
    if ($label) {
        $stmt2 = $db->prepare("UPDATE form_fields SET label=?, field_type=?, options=?, required=?, sort=? WHERE id=?");
        $stmt2->execute([$label, $type, $options, $required, $sort, $id]);
        header("Location: forms_fields.php?form_id=$form_id");
        exit;
    } else {
        $msg = '请填写完整信息';
    }
}
?>
<div class="content-card" style="max-width:420px;margin:48px auto;">
    <div class="card-header">
        <div class="card-title">编辑字段</div>
        <div class="card-actions"><a href="forms_fields.php?form_id=<?=$form_id?>">返回</a></div>
    </div>
    <?php if($msg): ?><div style="color:red;"><?=$msg?></div><?php endif;?>
    <form method="post">
        <div class="form-group" style="margin-bottom:18px;">
            <label>字段名称*</label>
            <input type="text" name="label" required class="input" autocomplete="off" value="<?=admin_esc($field['label'])?>">
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>类型*</label>
            <select name="field_type" class="input">
                <option value="text" <?=$field['field_type']=='text'?'selected':''?>>文本</option>
                <option value="number" <?=$field['field_type']=='number'?'selected':''?>>数字</option>
                <option value="select" <?=$field['field_type']=='select'?'selected':''?>>下拉选择</option>
            </select>
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>选项(仅select类型用,逗号分隔)</label>
            <input type="text" name="options" class="input" autocomplete="off" value="<?=admin_esc($field['options'])?>">
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>是否必填</label>
            <select name="required" class="input">
                <option value="0" <?=$field['required']?'':'selected'?>>可选</option>
                <option value="1" <?=$field['required']?'selected':''?>>必填</option>
            </select>
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>排序</label>
            <input type="number" name="sort" class="input" value="<?=admin_esc($field['sort'])?>">
        </div>
        <button type="submit" class="button">保存</button>
    </form>
</div>
<?php require_once 'footer.php'; ?>