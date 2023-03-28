<?php
namespace Level\VOR;
use Elastic\Elasticsearch\ClientBuilder;

require_once "vendor/autoload.php";
require_once "config.php";

$materials = json_decode(file_get_contents('data/materials.json'));

$es = new \Level\VOR\ElasticSearch(ELASTIC_SEARCH_APIKEY, ES_INDEX_MATERIAL);
$morphy = new Morphy();
echo '<pre>';
for ($i = 0; $i < count($materials); $i++) {
    $m = $materials[$i];
    if (property_exists($m, 'material')) {
        $subject = $morphy->getSubject($m->material);
        $cnt = count($subject);
        if ($cnt > 0) {
          $m->tags = implode(' ', $subject);
        }
        $es->newDocument($m, $m->mcode . "_$i");
    }
}
