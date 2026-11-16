<?php
// 必须放在文件最顶部，前面不能有任何输出（包括空格、注释、换行）
require_once '../../header.php';
require_login();
$user = current_user();
$db = get_db();

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$project_id = intval($_GET['project_id'] ?? 0);
if (!$project_id) exit('参数错误');

// 查询明细，包含支付状态和支付时间
$stmt = $db->prepare("SELECT customer_tag, customer_name, amount, remark, created_at, pay_status, paid_at FROM bills_items WHERE project_id=? AND user_id=? AND status=1 ORDER BY id DESC");
$stmt->bind_param('ii', $project_id, $user['id']);
$stmt->execute();
$result = $stmt->get_result();

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// 表头
$headers = ['客户标识', '客户姓名', '应收金额', '备注', '创建时间', '支付状态', '支付时间'];
$sheet->fromArray($headers, NULL, 'A1');

// 数据
$row = 2;
while ($item = $result->fetch_assoc()) {
    $sheet->setCellValue("A$row", $item['customer_tag']);
    $sheet->setCellValue("B$row", $item['customer_name']);
    $sheet->setCellValue("C$row", $item['amount']);
    $sheet->setCellValue("D$row", $item['remark']);
    $sheet->setCellValue("E$row", $item['created_at']);
    $sheet->setCellValue("F$row", $item['pay_status'] == 1 ? '已支付' : '未支付');
    $sheet->setCellValue("G$row", $item['paid_at'] ? $item['paid_at'] : '-');
    $row++;
}

// 清除缓冲区，防止乱码和多余输出
if (ob_get_length()) ob_end_clean();

// 输出为下载
$filename = '账单明细_' . date('Ymd_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment;filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>