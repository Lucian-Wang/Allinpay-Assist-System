<?php
require_once '../../header.php';
require_login();
$user = current_user();
$db = get_db();

require_once '../../vendor/autoload.php'; // PhpSpreadsheet 自动加载

use PhpOffice\PhpSpreadsheet\IOFactory;

$project_id = intval($_GET['project_id'] ?? 0);
if (!$project_id) exit('参数错误');

$err = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $err = '上传文件失败';
    } else {
        try {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['xlsx', 'xls'])) {
                throw new Exception('仅支持xlsx/xls格式');
            }
            $spreadsheet = IOFactory::load($file['tmp_name']);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
            
            $rowCount = 0;
            foreach ($rows as $i => $row) {
                if ($i == 0) continue; // 跳过表头
                $customer_tag = trim($row[0] ?? '');
                $customer_name = trim($row[1] ?? '');
                $amount = floatval($row[2] ?? 0);
                $remark = trim($row[3] ?? '');

                if ($customer_tag === '' && $customer_name === '') continue;
                if ($amount <= 0) continue;

                $stmt = $db->prepare("INSERT INTO bills_items (project_id, user_id, customer_tag, customer_name, amount, remark, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())");
                $stmt->bind_param('iissds', $project_id, $user['id'], $customer_tag, $customer_name, $amount, $remark);
                $stmt->execute();
                $stmt->close();
                $rowCount++;
            }
            $success = "导入成功，共导入 $rowCount 条数据。";
        } catch (Exception $e) {
            $err = '导入失败：' . $e->getMessage();
        }
    }
}
?>
<div class="content-card" style="max-width:500px;margin:40px auto;">
    <div class="card-header" style="margin-bottom:20px;">
        <div class="card-title">批量导入账单明细</div>
        <div class="card-actions">
            <a href="index.php?project_id=<?=$project_id?>" class="button">返回明细列表</a>
            <a href="import_template.xlsx" class="button">下载模板</a>
        </div>
    </div>
    <?php if($err): ?>
        <div style="color:#f56c6c;"><?=$err?></div>
    <?php elseif($success): ?>
        <div style="color:#52c41a;"><?=$success?></div>
    <?php endif;?>
    <form method="post" enctype="multipart/form-data">
        <div style="margin-bottom:18px;">请选择Excel文件（*.xlsx）：</div>
        <input type="file" name="file" accept=".xlsx,.xls" required><br>
        <button class="button" type="submit" style="margin-top:18px;">上传并导入</button>
    </form>
    <div style="color:#888;margin-top:18px;">
        <b>模板说明：</b><br>
        第一行为表头：客户标识、客户姓名、应收金额、备注（可选）<br>
        示例可
        <a href="import_template.xlsx" style="color:#3183f4">下载模板</a>
    </div>
</div>
<?php require_once '../../footer.php'; ?>