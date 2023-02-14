<?php

namespace Level\VOR;

use Elastic\EnterpriseSearch\AppSearch\Request;
use Elastic\EnterpriseSearch\AppSearch\Schema;
use Elastic\EnterpriseSearch\Client;

class Elastic extends Client
{
    private $search;

    function __construct()
    {
        $client = new Client([
            'host' => 'http://enterprisesearch:3002',
            'app-search' => [
                'token' => ELASTIC_APPSEARCH_TOKEN
            ]
        ]);
        $this->search = $client->appSearch();
    }

    protected function sanitizeKey(String $key): String
    {
        $res = substr(preg_replace('/\s{2,}/', ' ', preg_replace('/[\.,_\-!@#$%^&*]+/', ' ', $key)), 0, 128);
        if (($pos = strrpos($res, ' ', -1)) !== false)
            return substr($res, 0, $pos);
        else return $res;
    }

    private function search(String|array $key, String $engine, $firstonly = true): array|null
    {
        if (strlen($key) < 5) return null;

        # делаем запрос по ключу
        $result = $this->search->search(
            new Request\Search($engine, new Schema\SearchRequestParams($key))
        );

        # интерsесует только первая строка результата, если есть
        $result = $result->asArray();
        if (isset($result['results']) && is_array($result['results']) && count($result['results']) > 0)
            if ($firstonly) {
                $result['results'][0]['_meta']['_key'] = $key;
                return $result['results'][0];
            }
            else
                return $result['results'];
        else return null;
    }

    public function EsSearch($what)
    {
        // Elasticsearch query
        $searchParams = new Schema\EsSearchParams();
        $searchParams->query =
            [
                "bool" => [
                    "must" => [
                        [
                            "match" => [
                                "m_gcode" => "S23.01-41"
                            ]
                        ],
                        [
                            "match" => [
                                "excode" => "05.04.08"
                            ]
                        ]
                    ]
                ]
            ];

        // This is the Elasticsearch token API (Bearer)
        $elasticsearchApiKey = ELASTIC_APPSEARCH_TOKEN;

        $result = $this->search->searchEsSearch(
            (new Request\SearchEsSearch('works', '', $searchParams))
                ->setAuthorization($elasticsearchApiKey)
        );

        printf('<pre>%s</pre>', print_r($result->asArray(), true)); // Elasticsearch result in ['hits']['hits']
        var_dump($searchParams);
    }

    private function prepareKey($key): String
    {
        if ($key == null) return '';
        //
        $key = preg_replace('/\s{2,}/', ' ', trim($key));
        if (strlen($key) > 128) {
            $key = substr($key, 0, 128);
            # выкинуть последнее слово,т.к. может быть резаное
            if (($pos = strrpos($key, ' ', -1)) !== false) $key = substr($key, 0, $pos);
        }
        return $key;
    }

    private function clearName($name)
    {
        $name = preg_replace('/[\.,_\-!@#$%^&*]+/', ' ', $name);
        $re = '/[а-яА-Я\w\/,\.]*[0-9]+[а-яА-Я\w]*/';
        preg_match_all($re, $name, $matches, PREG_SET_ORDER, 0);
        $re = [];
        foreach ($matches as $m) {
            $re[] = $m[0];
            if (stripos($m[0], 'DN') === 0 && stripos($m[0], 'мм') === false) $re[] = $m[0] . 'мм';
            $name = str_replace($m[0], '', $name);
        }
        $re[] = $name;
        return implode(' ', $re);
    }

    public function getWork($m_vcode, $name, $excode = '')
    {
        $res = [];
        $r = [];
        /*        $keys = [
            '*' => ['key' => $excode . ' ' . $m_vcode . ' ' . $this->clearName($name)],
            'C' => ['key' => $excode . ' ' . $m_vcode],
        ];
        */
        $key = $excode . ' ' . $m_vcode;
        if ($excode != '') $key .= ' ' . $this->clearName($name);
        $key = $this->prepareKey($key);
        if ($key != '') {
            $r = $this->search($key, 'works', $excode == '');
            if ($r) {
                if ($excode != '') {
                    foreach ($r as $i) {
                        if (strpos($i['excode']['raw'], $excode) !== false) {
                            $i['_meta']['method'] = '*';
                            return ($i);
                        }
                    }
                } else {
                    $r['_meta']['method'] = 'C';
                    return $r;
                }
            }
        }
        return null;
    }

    public function getMaterial($brand, $article, $name)
    {
        $name = $this->clearName($name);

        $res = [];
        $engine = 'level-engine';
        $res['C'] = $this->search($this->prepareKey($article), $engine);
        # B
        $res['B'] = $this->search($this->prepareKey($name), $engine);
        # CB
        $key = sprintf('%s %s', $article, $name);
        $res['CB'] = $this->search($this->prepareKey($key), $engine);
        # CEB
        $key = trim(sprintf('%s %s %s', $article, $brand, $name));
        $res['CEB'] = $this->search($this->prepareKey($key), $engine);

        $score = 0;
        $method = '';
        foreach ($res as $k => $v) {
            if ($v && $v['_meta']['score'] > $score) {
                $score = $v['_meta']['score'];
                $method = $k;
            }
        }
        if ($method == '') return null;

        $res[$method]['_meta']['method'] = $method;
        return $res[$method];
    }

    public function getWork2($m_vcode, $m_name, $excode)
    {
        if ($excode != '') {
            $key = 'excode';
            $val = $excode;
        } else {
            $key = 'wname';
            $val = $this->clearName($m_name);
        }
        // Elasticsearch query
        $searchParams = new Schema\EsSearchParams();
        $searchParams->query =
            [
                "bool" => [
                    "must" => [
                        [
                            "match" => [
                                "m_gcode" => $m_vcode
                            ]
                        ],
                        [
                            "match" => [
                                "$key" => $val
                            ]
                        ]
                    ]
                ]
            ];

        // This is the Elasticsearch token API (Bearer)
        $elasticsearchApiKey = ELASTIC_APPSEARCH_TOKEN;

        $result = $this->search->searchEsSearch(
            (new Request\SearchEsSearch('works', '', $searchParams))
                ->setAuthorization($elasticsearchApiKey)
        );

        if ($result) $result = $result->asArray();
        //echo '<pre>' . $m_vcode . ' ' . $excode . '</pre>';        var_dump($result['hits']['hits'][0]);        die();

        if (is_array($result) && array_key_exists('hits', $result) && $result['hits']['total']['value'] > 0) {
            $result['hits']['hits'][0]['_source']['_meta']['score'] = $result['hits']['hits'][0]['_score'];
            $result['hits']['hits'][0]['_source']['_meta']['method'] = 'M';

            return $result['hits']['hits'][0]['_source'];
        } else {
            return null;
        }
    }
}
