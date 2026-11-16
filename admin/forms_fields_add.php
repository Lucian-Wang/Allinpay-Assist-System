<?php
$page_title = '新增字段';
require_once 'header.php';
$db = get_admin_db();
$form_id = intval($_GET['form_id'] ?? 0);
if (!$form_id) exit('参数错误');
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $label = trim($_POST['label'] ?? '');
    $type = $_POST['field_type'] ?? 'text';
    $options = trim($_POST['options'] ?? '');
    $required = intval($_POST['required'] ?? 0);
    $sort = intval($_POST['sort'] ?? 0);
    if ($label) {
        $stmt = $db->prepare("INSERT INTO form_fields (form_id, label, field_type, options, required, sort) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$form_id, $label, $type, $options, $required, $sort]);
        header("Location: forms_fields.php?form_id=$form_id");
        exit;
    } else {
        $msg = '请填写完整信息';
    }
}
?>
<div class="content-card" style="max-width:420px;margin:48px auto;">
    <div class="card-header">
        <div class="card-title">新增字段</div>
        <div class="card-actions"><a href="forms_fields.php?form_id=<?=$form_id?>">返回</a></div>
    </div>
    <?php if($msg): ?><div style="color:red;"><?=$msg?></div><?php endif;?>
    <form method="post">
        <div class="form-group" style="margin-bottom:18px;">
            <label>字段名称*</label>
            <input type="text" name="label" required class="input" autocomplete="off">
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>类型*</label>
            <select name="field_type" class="input">
                <option value="text">文本</option>
                <option value="number">数字</option>
                <option value="select">下拉选择</option>
            </select>
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>选项(仅select类型用,逗号分隔)</label>
            <input type="text" name="options" class="input" autocomplete="off">
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>是否必填</label>
            <select name="required" class="input">
                <option value="0">可选</option>
                <option value="1">必填</option>
            </select>
        </div>
        <div class="form-group" style="margin-bottom:18px;">
            <label>排序</label>
            <input type="number" name="sort" class="input" value="0">
        </div>
        <button type="submit" class="button">保存</button>
    </form>
</div>
<?php require_once 'footer.php'; ?>