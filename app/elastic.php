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
            if ($firstonly)
                return $result['results'][0];
            else
                return $result['results'];
        else return null;
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
        return preg_replace('/[\.,_\-!@#$%^&*]+/', ' ', $name);
    }

    public function getWork($m_vcode, $name, $excode = '')
    {
        $res = [];
        $r = [];
        $key = $this->prepareKey($excode . ' ' . $m_vcode . ' ' . $this->clearName($name));
        if ($key != '') {
            $r = $this->search($key, 'works', $excode == '');
            if ($r) {
                if ($excode != '') {
                    foreach($r as $i) {
                        if (strpos($i['excode']['raw'], $excode) !== false) {
                            $i['_meta']['method'] = '*';
                            return($i);
                        }
                    }
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
}
