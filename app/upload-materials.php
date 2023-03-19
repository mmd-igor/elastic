<?php
use Elastic\Elasticsearch\ClientBuilder;

require_once "vendor/autoload.php";
require_once "config.php";

$materials = json_decode(file_get_contents('data/materials.json'));

$es = new \Level\VOR\ElasticSearch(ELASTIC_SEARCH_APIKEY, ES_INDEX_MATERIAL);

for ($i = 0; $i < count($materials); $i++) {
    $m = $materials[$i];
    if (property_exists($m, 'material')) {
        $es->newDocument($m, $m->mcode . "_$i");
    }
}
