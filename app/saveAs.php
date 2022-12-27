<?php
require_once 'vendor/autoload.php';

use \PhpOffice\PhpSpreadsheet\Reader\Html;
use \PhpOffice\PhpSpreadsheet;

$reader = new \PhpOffice\PhpSpreadsheet\Reader\Html();
$spreadsheet = $reader->loadFromString($_POST['table']);

$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xls');
$fname = 'data/' . md5(random_bytes(8)) . 'xls';
$writer->save($fname); 

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// заставляем браузер показать окно сохранения файла
header('Content-Description: File Transfer');
header('Content-Type: application/ms-excel');
header('Content-Disposition: attachment; filename=table.xls');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($fname));
// читаем файл и отправляем его пользователю
readfile($fname);

unlink($fname);
exit;
