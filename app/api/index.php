<?php
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');
//echo "Проверка"; die();
//echo print_r($_REQUEST, true); exit;

require '../vendor/autoload.php';
require_once '../config.php';

function getParam($pname, $def = null) {
    return array_key_exists($pname, $_GET) ? $_GET[$pname] : $def;
}

$elastic = new \Level\VOR\ElasticSearch(ELASTIC_SEARCH_APIKEY, ES_INDEX_MATERIAL);

$material = $elastic->getMaterial(getParam('brand'), getParam('article'), getParam('name'));

echo $material ? json_encode($material, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '{"result": "not found"}';

