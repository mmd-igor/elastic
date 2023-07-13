<?php

namespace Level\VOR;

ini_set('memory_limit', '256M');

use ElasticSearch;
use Exception;
use GuzzleHttp\Psr7\Message;
use PhpOffice\PhpSpreadsheet\Calculation\Logical\Boolean;

require 'vendor/autoload.php';

$header = [
    'rcode' => 'раздел код',
    'razdel' => 'раздел',
    'gcode' => 'группа код',
    'group' => 'группа',
    'vcode' => 'вид код',
    'view' => 'вид',
    'bcode' => 'производитель код',
    'brand' => 'производитель',
    'dcode' => 'описание код',
    'description' => 'описание',
    'scode' => 'размер код',
    'size' => 'размер',
    'mcode' => 'материал код',
    'material' => 'материал',
    'meas' => 'ед. измерения',
    'k1' => 'коэффициент расхода',
    'k2' => 'коэффициент перевода',
    'price_brutto' => 'цена с ндс',
    'nds' => 'ндс',
    'pdate' => 'дата цены'
];

$result = [];

if ($argc > 1) {
    $result = ProcessFile($argv[1]);
    //
} else {
    $dir = __DIR__ . '/data/bigmat';
    foreach (glob("$dir/*.xlsx") as $file) {
        $result = array_merge($result, ProcessFile($file));
    }
}

function likeHeader($row): array
{
    global $header;
    $res = [];
    foreach ($row as $k => $v) {
        $v = mb_strtolower(trim($v));
        $key = array_search($v, $header);
        if ($key !== false) $res[$k] = $key;
    }
    if (count($res) > 5) return $res;
    else return [];
}

function ProcessFile($file): array
{
    global $header;
    echo basename($file);

    try {
        $xls = new Specification($file);
        $rows = $xls->ParseAllSheets();
        //
    } catch (\Exception $e) {
        echo ' ', $e->getMessage(), PHP_EOL;
        return [];
    }

    echo " total: ", count($rows);

    $keys = [];
    foreach ($rows as $row) {
        $keys = likeHeader($row);
        if (!empty($keys)) break;
    }

    foreach ($rows as &$row) {
        $new = [];
        foreach ($row as $k => $v) if (array_key_exists($k, $keys)) $new[$keys[$k]] = $v;
        $row = $new;
    }

    $rows = array_filter($rows, function ($row) {
        $A = trim($row['rcode']) ?? false;
        if (!$A || (strlen($A) < 3) || ($A[0] !== 'S') || (trim($row['mcode']) === '')) return false;
        return true;
    });

    foreach ($rows as $row) {
        if ((isset($row['k1']) && !is_numeric($row['k1'])) || (isset($row['k2']) && !is_numeric($row['k2']))) {
            print_r($row);
            throw new Exception('blaaaa');
        }
    }

    echo " core: ", count($rows) . PHP_EOL;
    unset($xls);

    return $rows;
}

$h = fopen('result.json', 'w+');
fwrite($h, json_encode(array_values($result), JSON_UNESCAPED_UNICODE));
fclose($h);


/* 

php ./xls2json.php "data/bigmat/S17_Арматура трубопроводная.xlsx" 
php ./xls2json.php "data/bigmat/S01_Сваи.xlsx"
php ./xls2json.php "data/bigmat/S12_Двери,люки, ворота.xlsx"
php ./xls2json.php
php ./xls2json.php "data/bigmat/S09_Отделочные материалы и изделия.xlsx"
php ./xls2json.php "data/bigmat/S24_Оборудование, устройства и аппаратура электрические.xlsx"
php ./xls2json.php "data/bigmat/S08_Гидро-пароизоляционные материалы и битумная продукция.xlsx"

*/
