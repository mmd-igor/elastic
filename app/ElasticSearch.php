<?php
// /\b(l|толщ[инаой]*|ду?|ø|dn|d|-)?\s*[=\s]?\s*(\d+\/?\d+)\s*(?:\b|м{2}|m{2}|x|х)/ui
namespace Level\VOR;

use Elastic\Elasticsearch\ClientBuilder;

class ElasticSearch
{
    private $client = null;
    private $index = 'materials_v5';
    private $morphy = null;

    function __construct(string $apikey, string $index)
    {
        $this->client = ClientBuilder::create()
            ->setHosts(['https://es01:9200'])
            ->setApiKey($apikey)
            ->setCABundle('/certs/ca/ca.crt') # it must be mount with container
            ->build();
        $this->index = $index;
        $this->morphy = new Morphy();
    }

    public function getMaterial($brand, $article, $name)
    {
        if (trim($name) == '') return null;
        $params = ['index' => $this->index, 'body' => ['size' => 1]];

        $key = trim("$article $name");

        $sizes = [];
        // отлов вида 12x23[x45]
        $re = '/\d+(?:[,\.]\d+)*\s*[xх]\s*\d+(?:[,\.]\d+)*(?:\s*[xх]\s*\d+(?:[,\.]\d+)*)?/ui';
        if (preg_match_all($re, $key, $matches, PREG_SET_ORDER, 0) !== false) {
            foreach ($matches as $m) {
                $m0 = str_replace(' ', '', $m[0]);
                $sizes[0][] = $m0;
                $c = 0;
                foreach (['x' => 'х', 'х' => 'x'] as $k => $v) {
                    $s = str_replace($k, $v, $m0, $c);
                    if ($c != 0) $sizes[0][] = $s;
                }
            }
        }
        // только для воздуховодов
        //$re = 'Воздуховод';
        //if (stripos($name, $re) !== false) {
        //    $params['body']['query']['bool']['should'][] = (object)['match' => ['group' => $re]];
        if (is_array($sizes[0]) && count($sizes[0]) > 0) $params['body']['query']['bool']['should'][] = (object)['match' => ['material' => 'прямоугольный']];
        //}
        // только для труб
        $re = 'Труба';
        if (stripos($name, $re) !== false) {
            $params['body']['query']['bool']['should'][] = (object)['match' => ['group' => $re]];
        }

        $re = '/\b(l|толщ[инаой]*|Ф|ду?|ø|dn|d|-|днар\.?|дн\.?)?\s*[=\s]?\s*(\d+(?:[,\.]\d+)*\/?\d+(?:[,\.]\d+)*)\s*(?:\b|м{2}|m{2}|x|х)/ui';
        if (preg_match_all($re, $name, $matches, PREG_SET_ORDER, 0) !== false && is_array($matches) && is_array($matches[0]) && count($matches[0]) == 3) {
            foreach ($matches as $m) {
                $sizes[0][] = (string)floatval($m[2]);
                if ($m[1] != '') $sizes[1][] = (strtoupper($m[1]) == 'L' ? 'L' : 'D') . floatval($m[2]);
            }
        }

        if (count($sizes)) {
            if (count($sizes[0]) > 0) {
                $params['body']['query']['bool']['should'][] = (object)['terms' => (object)['size' => $sizes[0]]];
            }
            if (is_array($sizes[1]) && count($sizes[1]) > 0) {
                $params['body']['query']['bool']['should'][] = (object)['terms' => (object)['scode' => $sizes[1]]];
            }
        }

        //$re = '/\bТруб(?:а|ы|ка|ки)|Заглушка|Крестовина|Муфта|Отвод\s+\d+°\s?|Патрубок|Переход|Ревизия|Тройник|Колено\s+\d+°\s?|Фланец|Адаптер|Устройство|Клапан|Воронка|Трап|Комплект\s+электрообогрева|Металлоконструкции|Соединитель|Хомут|Цилиндры?\b/ui';
        //$re = '/\bТруб(?:а|ы|ка|ки)|Заглушка|Крестовина|Муфта|Отвод|Патрубок|Переход|Ревизия|Тройник|Колено|Фланец|Адаптер|Устройство|Клапан|Воронка|Трап|Комплект|Металлоконструкции|Гильза|Соединитель|Хомут|РаДиатор|конвектор|Цилиндры?\b/ui';
        //if (false !== preg_match_all($re, $name, $matches, PREG_SET_ORDER, 0) && count($matches) == 1) {
        //$params['body']['query']['bool']['must'][] = (object)['match_phrase' => (object)['material' => $matches[0][0]]];
        //}

        if (!empty($name)) {
            $params['body']['query']['bool']['must'][] = (object)['multi_match' => (object)['query' => "$key", 'fields' => ['material', 'description', 'razdel']]]; //, 'group', 'view']]];
            $subjects = $this->morphy->getSubject($name);
            if (count($subjects) > 0) {
                $subjects = implode(' ', $subjects);
                if (!empty($name)) $params['body']['query']['bool']['should'][] = (object)['multi_match' => (object)['query' => "$subjects", 'fields' => ['group^2', 'view^4']]];
            }
        }

        //if (!empty($size)) {
        //$params['body']['query']['bool']['should'][] = (object)['match' => ['size' => (string)$size]];
        //  $params['body']['query']['bool']['should'][] = (object)['match' => ['scode' => $sz_pfx . $size]];
        //}

        if (!empty($brand))  $params['body']['query']['bool']['should'][] = (object)['multi_match' => (object)['query' => $brand, 'fields' => ['brand^2', 'group', 'razdel']]];

        $result = $this->client->search($params);
        if ($result) $result = $result->asArray();

        if (is_array($result) && array_key_exists('hits', $result) && $result['hits']['total']['value'] > 0) {
            $result['hits']['hits'][0]['_source']['_meta']['score'] = $result['hits']['hits'][0]['_score'];
            $result['hits']['hits'][0]['_source']['_meta']['method'] = 'M';
            $result['hits']['hits'][0]['_source']['_key'] = json_encode($params['body'], JSON_UNESCAPED_UNICODE | (isset($_GET['oneline']) && $_GET['oneline'] == '1' ? JSON_PRETTY_PRINT : 0));

            return $result['hits']['hits'][0]['_source'];
        } else {
            return null;
        }
    }

    public function newDocument(object $doc, $id = null)
    {
        $params = ['index' => $this->index, 'body' => json_encode($doc)];
        if ($id) {
            $params['id'] = $id;
            //$params['op_type'] = 'create';
            //$params['version_type'] = 'external';
        }
        $this->client->index($params);
        return true;
    }
}
