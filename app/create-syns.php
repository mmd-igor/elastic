<?php

require __DIR__ . '/config.php';

$url = 'http://enterprisesearch:3002/api/as/v1/engines/level-engine/synonyms';
$template = '{ "synonyms": ["DN%d", "DN%dмм", "DN%d мм", "DN %dмм", "D%d", "Д=%dмм", "Д=%d мм", "DN %d", "Ø%d"] }';
$diams = [15, 20, 25, 32, 40, 50, 57, 65, 76, 80, 89, 108, 110, 160];

$syns = [
  ['мм', 'mm', 'мm', 'mм'],
  ['Гофротруба', 'Г офротруба'],
  ['Гофрированная', 'Г офрированная']
];

function curl_prepare($url, $method = 'GET') {
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Bearer ' . ELASTIC_APPSEARCH_TOKEN));
  return($ch);
}

echo '<pre>';
$ch = curl_prepare($url); 
$result = curl_exec($ch);
curl_close($ch);
$result = json_decode($result);
if (is_object($result) && property_exists($result, 'results') && is_array($result->results)) {
  foreach($result->results as $syn) {
    $ch = curl_prepare(sprintf('%s/%s', $url, $syn->id), 'DELETE');
    $result = curl_exec($ch);
    curl_close($ch);
    print($result);
  }
}

foreach ($diams as $d) {
  $ch = curl_prepare($url, 'POST');
  curl_setopt($ch, CURLOPT_POSTFIELDS, str_replace('%d', $d, $template));
  $result = curl_exec($ch);
  curl_close($ch);
  var_dump ($result);
}

foreach ($syns as $s) {
  $ch = curl_prepare($url, 'POST');
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['synonyms' => $s]));
  $result = curl_exec($ch);
  curl_close($ch);
  var_dump ($result);
}

echo '</pre>';