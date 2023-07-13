<?php
namespace Level\VOR;

//error_reporting(0);

header('Content-Type: application/json; charset=utf-8');

require '../vendor/autoload.php';
require_once '../config.php';

$elastic = new \Level\VOR\ElasticSearch(ELASTIC_SEARCH_APIKEY, ES_INDEX_MATERIAL);

$material = $elastic->getMaterial(getParam('brand'), getParam('article'), getParam('name'));
if (is_array($material)) {
    if (array_key_exists('_key', $material)) 
        $material['_key'] = json_decode($material['_key']);
    if (array_key_exists('_meta', $material)) 
        $material['score'] = $material['_meta']['score'] ?? 0;
}

$fields = getParam('fields');
if ($fields) {
    $fields = explode(',', $fields);
    if (count($fields) == 1) echo array_key_exists($fields[0], $material) ? $material[$fields[0]] : sprintf('{"error":"unknown field: `%s`"}', $fields[0]);
    else {
        $res = [];
        foreach($fields as $f) if (array_key_exists($f, $material)) $res[$f] = $material[$f];
        echo json_encode($res, JSON_UNESCAPED_UNICODE | (getParam('oneline', 0) == 1 ? 0 : JSON_PRETTY_PRINT));
    }
} else
    echo $material ? json_encode($material, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '{"result": "not found"}';