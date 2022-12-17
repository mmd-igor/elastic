<?php

use Elastic\EnterpriseSearch\AppSearch\Request;
use Elastic\EnterpriseSearch\AppSearch\Schema;
use Elastic\EnterpriseSearch\Client;

class Elastic extends Client
{
    private $search;
    private $engineName = 'level-engine';

    function __construct()
    {
        $client = new Client([
            'host' => 'http://enterprisesearch:3002',
            'app-search' => [
                'token' => 'private-ck7njn1r3rm4b6zdxise9o7u'
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

    public function getRecord($brand, $article, $name)
    {
        # удалить пунктуацию и спецсимволыиз имени
        $name = preg_replace('/[\.,_\-!@#$%^&*]+/', ' ', $name);
        # склеить ключ, обрезать концевые пробелы
        $key = trim(sprintf('%s %s %s', $brand, $article, $name));
        # выкинуть двойные (и более) пробелы, коррекция длины
        $key = substr(preg_replace('/\s{2,}/', ' ', $key), 0, 128);
        # выкинуть последнее слово,т.к. может быть резаное
        if (($pos = strrpos($key, ' ', -1)) !== false) $key = substr($key, 0, $pos);

        # делаем запрос по ключу
        $result = $this->search->search(
            new Request\Search($this->engineName, new Schema\SearchRequestParams($key))
        );

        # интенесует только первая строка результата, если есть
        $result = $result->asArray();
        if (isset($result['results']) && is_array($result['results']) && count($result['results']) > 0)
            return $result['results'][0];
        else return null;
    }
}
