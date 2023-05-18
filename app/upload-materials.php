<?php

namespace Level\VOR;

//use Elastic\Elasticsearch\ClientBuilder;

require_once "vendor/autoload.php";
require_once "config.php";

$materials = json_decode(file_get_contents('data/materials.json'));
logf("%s materials loaded", count($materials));

$es = new \Level\VOR\ElasticSearch(ELASTIC_SEARCH_APIKEY, 'materials_v6');
$morphy = new Morphy();

// отлов дубликатов, делаем merge свойств, результат - в $arr
$arr = [];
for ($i = 0; $i < count($materials); $i++) {
  $m = $materials[$i];
  if (property_exists($m, 'material') && property_exists($m, 'mcode')) {
    if (array_key_exists($m->mcode, $arr))
      $arr[$m->mcode] = (object)array_merge((array)($arr[$m->mcode]), (array)($m));
    else $arr[$m->mcode] = $m;
  }
}
unset($materials);

logf("%s uniqual materials merged", count($arr));

// основной цикл вставки
foreach ($arr as $m) {
  $subject = $morphy->getSubject($m->material);
  $cnt = count($subject);
  if ($cnt > 0) {
    $m->tags = implode(' ', $subject);
  }
  $es->newDocument($m, $m->mcode);
}

log("done.");