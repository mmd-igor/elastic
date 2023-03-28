<?php

namespace Level\VOR;

require 'vendor/autoload.php';
echo '<pre>';

// Создаем экземпляр объекта PhpMorphy
$morphy = new Morphy();

// Текст, в котором нужно найти подлежащее
$text = "ВНУТРИПОЛЬНЫЙ";
var_dump($morphy->getGramInfo($text));

?>
</pre><?php exit;

        $sizes = [];
        $name = 'Диафрагма в головке пожарных кранов ∅13,5';
        $re = '/\b(l|толщ[инаой]*|ду?|ø|dn|d|-)?\s*[=\s]?\s*(\d+(?:[,\.]\d+)*\/?\d+(?:[,\.]\d+)*)\s*(?:\b|м{2}|m{2}|x|х)/ui';
        if (preg_match_all($re, $name, $matches, PREG_SET_ORDER, 0) !== false && is_array($matches) && is_array($matches[0]) && count($matches[0]) == 3) {
            $size = $matches[0][2];
            $sz_pfx = $matches[0][1];
            if (strtoupper($sz_pfx) != 'L') $sz_pfx = 'D';
            $sizes[] = (string)$size;
        }
        var_dump($sizes);
        exit;

        // отлов вида 12x23[x45]
        $re = '/\d+(?:[,\.]\d+)*[xх]\d+(?:[,\.]\d+)*(?:[xх]\d+(?:[,\.]\d+)*)?/ui';
        if (preg_match_all($re, 'ППгнг(А)-FRHF 1х120', $matches, PREG_SET_ORDER, 0) !== false) {
            foreach ($matches as $m) {
                $sizes[] = $m[0];
            }
        }
        var_dump($sizes);
        exit;



        $re = '/\b(L|ду?|ø|dn|d)?\s*[=\s]?\s*(\d+)\s*(?:\b|м{2}|m{2}|x|х)/ui';
        $str = 'Вытяжная установка l=145 м3\\/час, Р=450 Па';

        preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0);


        // Print the entire match result
        var_dump($matches);
        exit;

        include 'vendor/autoload.php';

        $es = new Level\VOR\ElasticSearch('Vy1mdDdvWUJNZG5WbmFpeXQ1U2M6RWhDSW05WkxRaC1LcC1rajVLUWI5Zw==', 'materials_v5');
        $materials = json_decode(file_get_contents(__DIR__ . '/data/materials.json'));
        for ($i = 0; $i < count($materials); $i++) {
            //for ($i = 0; $i < 25; $i++) {
            $m = $materials[$i];
            if (property_exists($m, 'material')) {
                $es->newDocument($m, $m->mcode . "_$i");
            }
        }
        unset($es);
        exit;




        define('CHAR_LENGTH', 2);
        function stem($word)
        {
            $a = rv($word);
            return $a[0] . step4(step3(step2(step1($a[1]))));
        }

        function rv($word)
        {
            $vowels = array('а', 'е', 'и', 'о', 'у', 'ы', 'э', 'ю', 'я');
            $flag = 0;
            $rv = $start = '';
            for ($i = 0; $i < strlen($word); $i += CHAR_LENGTH) {
                if ($flag == 1) $rv .= substr($word, $i, CHAR_LENGTH);
                else $start .= substr($word, $i, CHAR_LENGTH);
                if (array_search(substr($word, $i, CHAR_LENGTH), $vowels) !== FALSE) $flag = 1;
            }
            return array($start, $rv);
        }

        function step1($word)
        {
            $perfective1 = array('в', 'вши', 'вшись');
            foreach ($perfective1 as $suffix)
                if (substr($word, - (strlen($suffix))) == $suffix && (substr($word, -strlen($suffix) - CHAR_LENGTH, CHAR_LENGTH) == 'а' || substr($word, -strlen($suffix) - CHAR_LENGTH, CHAR_LENGTH) == 'я'))
                    return substr($word, 0, strlen($word) - strlen($suffix));
            $perfective2 = array('ив', 'ивши', 'ившись', 'ывши', 'ывшись');
            foreach ($perfective2 as $suffix)
                if (substr($word, - (strlen($suffix))) == $suffix)
                    return substr($word, 0, strlen($word) - strlen($suffix));
            $reflexive = array('ся', 'сь');
            foreach ($reflexive as $suffix)
                if (substr($word, - (strlen($suffix))) == $suffix)
                    $word = substr($word, 0, strlen($word) - strlen($suffix));
            $adjective = array('ее', 'ие', 'ые', 'ое', 'ими', 'ыми', 'ей', 'ий', 'ый', 'ой', 'ем', 'им', 'ым', 'ом', 'его', 'ого', 'ему', 'ому', 'их', 'ых', 'ую', 'юю', 'ая', 'яя', 'ою', 'ею');
            $participle2 = array('ем', 'нн', 'вш', 'ющ', 'щ');
            $participle1 = array('ивш', 'ывш', 'ующ');
            foreach ($adjective as $suffix) if (substr($word, - (strlen($suffix))) == $suffix) {
                $word = substr($word, 0, strlen($word) - strlen($suffix));
                foreach ($participle1 as $suffix)
                    if (substr($word, - (strlen($suffix))) == $suffix && (substr($word, -strlen($suffix) - CHAR_LENGTH, CHAR_LENGTH) == 'а' || substr($word, -strlen($suffix) - CHAR_LENGTH, CHAR_LENGTH) == 'я'))
                        $word = substr($word, 0, strlen($word) - strlen($suffix));
                foreach ($participle2 as $suffix)
                    if (substr($word, - (strlen($suffix))) == $suffix)
                        $word = substr($word, 0, strlen($word) - strlen($suffix));
                return $word;
            }
            $verb1 = array('ла', 'на', 'ете', 'йте', 'ли', 'й', 'л', 'ем', 'н', 'ло', 'но', 'ет', 'ют', 'ны', 'ть', 'ешь', 'нно');
            foreach ($verb1 as $suffix)
                if (substr($word, - (strlen($suffix))) == $suffix && (substr($word, -strlen($suffix) - CHAR_LENGTH, CHAR_LENGTH) == 'а' || substr($word, -strlen($suffix) - CHAR_LENGTH, CHAR_LENGTH) == 'я'))
                    return substr($word, 0, strlen($word) - strlen($suffix));
            $verb2 = array('ила', 'ыла', 'ена', 'ейте', 'уйте', 'ите', 'или', 'ыли', 'ей', 'уй', 'ил', 'ыл', 'им', 'ым', 'ен', 'ило', 'ыло', 'ено', 'ят', 'ует', 'уют', 'ит', 'ыт', 'ены', 'ить', 'ыть', 'ишь', 'ую', 'ю');
            foreach ($verb2 as $suffix)
                if (substr($word, - (strlen($suffix))) == $suffix)
                    return substr($word, 0, strlen($word) - strlen($suffix));
            $noun = array('а', 'ев', 'ов', 'ие', 'ье', 'е', 'иями', 'ями', 'ами', 'еи', 'ии', 'и', 'ией', 'ей', 'ой', 'ий', 'й', 'иям', 'ям', 'ием', 'ем', 'ам', 'ом', 'о', 'у', 'ах', 'иях', 'ях', 'ы', 'ь', 'ию', 'ью', 'ю', 'ия', 'ья', 'я');
            foreach ($noun as $suffix)
                if (substr($word, - (strlen($suffix))) == $suffix)
                    return substr($word, 0, strlen($word) - strlen($suffix));
            return $word;
        }

        function step2($word)
        {
            return substr($word, -CHAR_LENGTH, CHAR_LENGTH) == 'и' ? substr($word, 0, strlen($word) - CHAR_LENGTH) : $word;
        }

        function step3($word)
        {
            $vowels = array('а', 'е', 'и', 'о', 'у', 'ы', 'э', 'ю', 'я');
            $flag = 0;
            $r1 = $r2 = '';
            for ($i = 0; $i < strlen($word); $i += CHAR_LENGTH) {
                if ($flag == 2) $r1 .= substr($word, $i, CHAR_LENGTH);
                if (array_search(substr($word, $i, CHAR_LENGTH), $vowels) !== FALSE) $flag = 1;
                if ($flag = 1 && array_search(substr($word, $i, CHAR_LENGTH), $vowels) === FALSE) $flag = 2;
            }
            $flag = 0;
            for ($i = 0; $i < strlen($r1); $i += CHAR_LENGTH) {
                if ($flag == 2) $r2 .= substr($r1, $i, CHAR_LENGTH);
                if (array_search(substr($r1, $i, CHAR_LENGTH), $vowels) !== FALSE) $flag = 1;
                if ($flag = 1 && array_search(substr($r1, $i, CHAR_LENGTH), $vowels) === FALSE) $flag = 2;
            }
            $derivational = array('ост', 'ость');
            foreach ($derivational as $suffix)
                if (substr($r2, - (strlen($suffix))) == $suffix)
                    $word = substr($word, 0, strlen($r2) - strlen($suffix));
            return $word;
        }

        function step4($word)
        {
            if (substr($word, -CHAR_LENGTH * 2) == 'нн') $word = substr($word, 0, strlen($word) - CHAR_LENGTH);
            else {
                $superlative = array('ейш', 'ейше');
                foreach ($superlative as $suffix)
                    if (substr($word, - (strlen($suffix))) == $suffix)
                        $word = substr($word, 0, strlen($word) - strlen($suffix));
                if (substr($word, -CHAR_LENGTH * 2) == 'нн') $word = substr($word, 0, strlen($word) - CHAR_LENGTH);
            }
            if (substr($word, -CHAR_LENGTH, CHAR_LENGTH) == 'ь') $word = substr($word, 0, strlen($word) - CHAR_LENGTH);
            return $word;
        }

        require_once __DIR__ . '/morphy/src/common.php';

        class morphyus
        {
            private $morphy        = null;

            function __construct()
            {
                $directory            = __DIR__ . '/morphy/dicts';
                $language             = 'ru_RU';
                $options['storage'] = PHPMORPHY_STORAGE_FILE;

                //var_dump($GLOBALS['__phpmorphy_strtolower']('KJHGFKJGHK'));
                // Инициализация библиотеки //
                $this->morphy = new phpMorphy($directory, $language, $options);
            }

            function Do($text)
            {
                var_dump($this->morphy->getPartOfSpeech(explode(' ', $text)));
            }
        }

        //echo stem('Труба')  . PHP_EOL;
        $m = new morphyus();
        $m->Do('РаДиатор Prado Classic тип 22 в комплекте пробка глухая-1шт., воздухоотводчик-1шт.,кронштейны крепления-2шт.');
