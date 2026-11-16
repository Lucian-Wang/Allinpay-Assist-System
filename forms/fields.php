<?php
require_once '../header.php';
require_login();
$user = current_user();
$db = get_db();
$form_id = intval($_GET['form_id'] ?? 0);

// 检查 forms 表存在
$stmt = $db->prepare("SELECT * FROM forms WHERE id=? AND user_id=?");
if (!$stmt) {
    die('SQL ERROR: ' . $db->error);
}
$stmt->bind_param('ii', $form_id, $user['id']);
$stmt->execute();
$form = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$form) exit('参数错误');

// 处理新增/编辑字段
$edit_field = null;
if (isset($_GET['edit'])) {
    $fid = intval($_GET['edit']);
    $stmt = $db->prepare("SELECT * FROM form_fields WHERE id=? AND form_id=?");
    if (!$stmt) die('SQL ERROR: ' . $db->error);
    $stmt->bind_param('ii', $fid, $form_id);
    $stmt->execute();
    $edit_field = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $label = trim($_POST['label'] ?? '');
    $type = $_POST['field_type'] ?? 'text';
    $options = trim($_POST['options'] ?? '');
    $required = intval($_POST['required'] ?? 0);
    $sort = intval($_POST['sort'] ?? 0);
    if ($label) {
        if (isset($_POST['fid']) && $_POST['fid']) { // 编辑
            $fid = intval($_POST['fid']);
            $stmt = $db->prepare("UPDATE form_fields SET label=?, field_type=?, options=?, required=?, sort=? WHERE id=? AND form_id=?");
            if (!$stmt) die('SQL ERROR: ' . $db->error);
            $stmt->bind_param('sssiiii', $label, $type, $options, $required, $sort, $fid, $form_id);
            $stmt->execute();
            $stmt->close();
        } else { // 新增
            $stmt = $db->prepare("INSERT INTO form_fields (form_id, label, field_type, options, required, sort) VALUES (?, ?, ?, ?, ?, ?)");
            if (!$stmt) die('SQL ERROR: ' . $db->error);
            $stmt->bind_param('isssii', $form_id, $label, $type, $options, $required, $sort);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: fields.php?form_id=$form_id");
        exit;
    }
}

// 删除字段
if (isset($_GET['del'])) {
    $fid = intval($_GET['del']);
    $db->query("DELETE FROM form_fields WHERE id=$fid AND form_id=$form_id");
    header("Location: fields.php?form_id=$form_id");
    exit;
}

// 字段排序处理
if (isset($_GET['sort']) && isset($_GET['fid'])) {
    $fid = intval($_GET['fid']);
    $dir = $_GET['sort'] === 'up' ? -1 : 1;
    // 查当前字段
    $stmt = $db->prepare("SELECT id, sort FROM form_fields WHERE id=? AND form_id=?");
    if (!$stmt) die('SQL ERROR: ' . $db->error);
    $stmt->bind_param('ii', $fid, $form_id);
    $stmt->execute();
    $cur = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($cur) {
        // 查要交换的字段
        $stmt = $db->prepare("SELECT id, sort FROM form_fields WHERE form_id=? AND sort " . ($dir == -1 ? '<' : '>') . " ? ORDER BY sort " . ($dir == -1 ? 'DESC' : 'ASC') . " LIMIT 1");
        if (!$stmt) die('SQL ERROR: ' . $db->error);
        $stmt->bind_param('ii', $form_id, $cur['sort']);
        $stmt->execute();
        $swap = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($swap) {
            // 交换sort
            $db->query("UPDATE form_fields SET sort={$swap['sort']} WHERE id={$cur['id']}");
            $db->query("UPDATE form_fields SET sort={$cur['sort']} WHERE id={$swap['id']}");
        }
    }
    header("Location: fields.php?form_id=$form_id");
    exit;
}

// 查询字段
$stmt = $db->prepare("SELECT * FROM form_fields WHERE form_id=? ORDER BY sort ASC, id ASC");
if (!$stmt) die('SQL ERROR: ' . $db->error);
$stmt->bind_param('i', $form_id);
$stmt->execute();
$fields = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<style>
body { background: #f7fafd; }
.content-card {
    max-width: 950px;
    margin: 48px auto;
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 8px 36px rgba(44,104,255,0.10);
    padding: 36px 36px 28px 36px;
}
.card-header { display: flex; justify-content: space-between; align-items: center; }
.card-title { font-size: 28px; font-weight: bold; color: #2486ff; }
.card-actions a { color: #2486ff; text-decoration: none; font-size: 18px; }
.field-form {
    display: flex;
    gap: 14px;
    margin: 20px 0 26px 0;
    align-items: center;
}
.field-form input, .field-form select {
    padding: 12px 16px;
    border: 1.5px solid #e3eaf5;
    border-radius: 10px;
    background: #f7fafd;
    font-size: 17px;
    outline: none;
    transition: border 0.2s;
}
.field-form input[type="number"] { width: 80px; }
.field-form input[type="text"] { width: 170px; }
.field-form select { width: 120px; }
.field-form .field-btn, .field-btn {
    background: #2486ff;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 17px;
    font-weight: bold;
    padding: 12px 14px;
    cursor: pointer;
    transition: background 0.18s;
}
.field-form .field-btn:hover, .field-btn:hover { background: #1677ff; }
.table {
    width: 100%; border-collapse: collapse; background: #fff;
}
.table th, .table td {
    padding: 13px 8px;
    border-bottom: 1.5px solid #e3eaf5;
    text-align: center;
    font-size: 16px;
}
.table th {
    background: #f7fafd;
    color: #2486ff;
    font-weight: bold;
}
.table tr:last-child td { border-bottom: none; }
.action-link {
    color: #2486ff;
    text-decoration: none;
    margin: 0 4px;
    cursor: pointer;
}
.action-link:hover { text-decoration: underline; }
@media (max-width: 950px) {
    .content-card { padding: 18px 2vw 12px 2vw; }
    .card-title { font-size: 22px; }
    .field-form { flex-wrap: wrap; gap: 10px; }
}
</style>
<div class="content-card">
    <div class="card-header">
        <div class="card-title">字段管理 - <?=esc($form['name'])?></div>
        <div class="card-actions"><a href="index.php">返回</a></div>
    </div>
    <form method="post" class="field-form">
        <input type="hidden" name="fid" value="<?= $edit_field ? $edit_field['id'] : '' ?>">
        <input type="text" name="label" placeholder="字段名称" required value="<?= $edit_field ? esc($edit_field['label']) : '' ?>">
        <select name="field_type">
            <option value="text" <?= $edit_field && $edit_field['field_type']=='text'?'selected':''; ?>>文本</option>
            <option value="number" <?= $edit_field && $edit_field['field_type']=='number'?'selected':''; ?>>数字</option>
            <option value="select" <?= $edit_field && $edit_field['field_type']=='select'?'selected':''; ?>>下拉选择</option>
        </select>
        <input type="text" name="options" placeholder="选项(逗号分隔，仅select用)" value="<?= $edit_field ? esc($edit_field['options']) : '' ?>">
        <select name="required">
            <option value="0" <?= $edit_field && !$edit_field['required']?'selected':''; ?>>可选</option>
            <option value="1" <?= $edit_field && $edit_field['required']?'selected':''; ?>>必填</option>
        </select>
        <input type="number" name="sort" placeholder="排序" value="<?= $edit_field ? esc($edit_field['sort']) : 0 ?>">
        <button type="submit" class="field-btn"><?= $edit_field ? '保存' : '添加字段' ?></button>
        <?php if($edit_field): ?>
            <a href="fields.php?form_id=<?=$form_id?>" class="field-btn" style="background:#eee;color:#2486ff;">取消</a>
        <?php endif;?>
    </form>
    <table class="table">
        <thead>
            <tr>
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
                <td><?=esc($f['label'])?></td>
                <td><?=esc($f['field_type'])?></td>
                <td><?=esc($f['options'])?></td>
                <td><?= $f['required'] ? '是' : '否' ?></td>
                <td>
                    <?=esc($f['sort'])?>
                    <a class="action-link" href="?form_id=<?=$form_id?>&sort=up&fid=<?=$f['id']?>" title="上移">↑</a>
                    <a class="action-link" href="?form_id=<?=$form_id?>&sort=down&fid=<?=$f['id']?>" title="下移">↓</a>
                </td>
                <td>
                    <a href="?form_id=<?=$form_id?>&edit=<?=$f['id']?>" class="action-link">编辑</a>
                    <a href="?form_id=<?=$form_id?>&del=<?=$f['id']?>" class="action-link" onclick="return confirm('确定删除?');">删除</a>
                </td>
            </tr>
        <?php endforeach;?>
        </tbody>
    </table>
</div>
<?php require_once '../footer.php'; ?>