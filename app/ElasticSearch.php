<?php

namespace Level\VOR;
use Elastic\Elasticsearch\ClientBuilder;

class ElasticSearch
{
    private $client = null;
    private $index = 'materials_v5';

    function __construct(string $apikey, string $index)
    {
        $this->client = ClientBuilder::create()
            ->setHosts(['https://es01:9200'])
            ->setApiKey($apikey)
            ->setCABundle(__DIR__ . '/assets/ca.crt')
            ->build();
        $this->index = $index;
    }

    public function getMaterial($brand, $article, $name)
    {
        $params = ['index' => $this->index, 'body' => ['size' => 1]];
        $re = '/\b(?:ду?|ø|dn|d)?\s*[=\s]?\s*(\d+)\s*(?:\b|м{2}|m{2}|x|х)/ui';
        preg_match_all($re, $name, $matches, PREG_SET_ORDER, 0);
        if (is_array($matches) && is_array($matches[0]) && count($matches[0]) == 2) {
            $size = (float)$matches[0][1];
        }

        $key = trim("$article $name");
        if (!empty($name)) $params['body']['query']['bool']['must'] = (object)['multi_match' => (object)['query' => "$key", 'fields' => ['material', 'description', 'razdel', 'group', 'view']]];
        if (!empty($size))  {
            $params['body']['query']['bool']['should'][] = (object)['match' => ['size' => (string)$size]];
            $params['body']['query']['bool']['should'][] = (object)['match' => ['scode' => "D$size"]];
        }
        if (!empty($brand))  $params['body']['query']['bool']['should'][] = (object)['multi_match' => (object)['query' => $brand, 'fields' => ['brand', 'group', 'razdel']]];

        $result = $this->client->search($params);
        if ($result) $result = $result->asArray();

        if (is_array($result) && array_key_exists('hits', $result) && $result['hits']['total']['value'] > 0) {
            $result['hits']['hits'][0]['_source']['_meta']['score'] = $result['hits']['hits'][0]['_score'];
            $result['hits']['hits'][0]['_source']['_meta']['method'] = 'M';
            $result['hits']['hits'][0]['_source']['_meta']['_key'] = json_encode($params['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);;

            return $result['hits']['hits'][0]['_source'];
        } else {
            return null;
        }
    }

    public function newDocument(object $doc, $id = null) {
        $params = ['index' => $this->index, 'body' => json_encode($doc)];
        if ($id) $params['id'] = $id; 
        $this->client->index($params);
    }
}
