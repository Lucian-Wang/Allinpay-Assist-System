<?php
// 依赖 endroid/qr-code, 安装命令：composer require endroid/qr-code
require_once '/www/wwwroot/allinpay.zhuli.pro/codes/vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;

// 1. 读取参数
$data = $_GET['data'] ?? '';
$id = $_GET['id'] ?? ''; // ID: 用于二维码下方文字
$company = $_GET['company'] ?? ''; // 公司名: 用于底部文字

if (!$data) {
    http_response_code(400);
    exit('缺少 data 参数');
}

// 2. 生成二维码图像资源
$qr = QrCode::create($data)
    ->setEncoding(new Encoding('UTF-8'))
    ->setErrorCorrectionLevel(new ErrorCorrectionLevelHigh())
    ->setSize(340) // 生成二维码尺寸（需与底图二维码区域大小一致）
    ->setMargin(0);

$writer = new PngWriter();
// 生成二维码并保存为临时文件，然后用GD读取
$tmpFile = tempnam(sys_get_temp_dir(), 'qr_');
$result = $writer->write($qr);
file_put_contents($tmpFile, $result->getString());
$qrcodeImg = imagecreatefrompng($tmpFile);
unlink($tmpFile); // 用完马上删除

// 3. 读取底图
$templatePath = '/www/wwwroot/allinpay.zhuli.pro/codes/allinpay_tpl.png'; // 你的品牌模板图片路径
if (!file_exists($templatePath)) {
    http_response_code(500);
    exit('找不到模板图片');
}
$templateImg = imagecreatefrompng($templatePath);

// 4. 粘贴二维码到底图指定区域
$dst_x = 125;      // 二维码区域左上角X坐标（像素）
$dst_y = 200;      // 二维码区域左上角Y坐标（像素）
$dst_size = 330;   // 二维码区域宽高（像素）

imagecopyresampled(
    $templateImg,     // 底图
    $qrcodeImg,       // 源二维码
    $dst_x, $dst_y,   // 粘贴到底图的X/Y
    0, 0,             // 二维码内起点
    $dst_size, $dst_size, // 粘贴宽高
    imagesx($qrcodeImg), imagesy($qrcodeImg) // 源二维码宽高
);

// 5. 绘制文字（ID和公司名）
// 字体文件路径（需准备支持中文的ttf字体，推荐msyh.ttf，放到assets目录）
$font = '/www/wwwroot/allinpay.zhuli.pro/codes/msyh.ttf';
// 字体颜色
$black = imagecolorallocate($templateImg, 51, 51, 51); // 深灰
$white = imagecolorallocate($templateImg, 255, 255, 255); // 白色
$companyBlue = imagecolorallocate($templateImg, 255,255,255); // 公司名白色

// 写ID: 如 "ID：100BXUCXTx2P"
if ($id && file_exists($font)) {
    $fontSize = 18; // 字体大小
    $id_text = "ID：$id";
    // 让ID居中于二维码下方白底
    $bbox = imagettfbbox($fontSize, 0, $font, $id_text);
    $textWidth = $bbox[2] - $bbox[0];
    $center_x = $dst_x + ($dst_size - $textWidth) / 2; // 横向居中
    $center_y = $dst_y + $dst_size + 34; // 二维码下方，适当调整
    imagettftext($templateImg, $fontSize, 0, $center_x, $center_y, $black, $font, $id_text);
}

// 写公司名（如 "中都财税服务有限公司"）
if ($company && file_exists($font)) {
    $fontSize = 20;
    $company_text = $company;
    $bbox = imagettfbbox($fontSize, 0, $font, $company_text);
    $textWidth = $bbox[2] - $bbox[0];
    $center_x = $dst_x + ($dst_size - $textWidth) / 2;
    $center_y = $dst_y + $dst_size + 90; // 再下一行，适当调整
    imagettftext($templateImg, $fontSize, 0, $center_x, $center_y, $companyBlue, $font, $company_text);
}

// 6. 输出最终图片
header('Content-Type: image/png');
imagepng($templateImg);

// 7. 内存释放
imagedestroy($templateImg);
imagedestroy($qrcodeImg);
exit;