<?php

namespace Level\VOR;

use \cijic\phpMorphy;

class Morphy extends \cijic\phpMorphy\Morphy
{
    private $stops = ['ТИП', 'ПЛОТНОСТЬ', 'ШИРИНА', 'ТОЛЩИНА', 'ДЛИНА', 'РАЗМЕР', 'ЦВЕТА', 'ГАБАРИТЫ', 'ИЗГОТОВЛЕНИЯ', 'СЕРИЯ', 'ЧАС', 'ВИС', 'КАТ', 'ПОРТ', 'ПРОТ', 'РУБЕЖ', 'ОРИОН', 'ВЫСОТА', 'ГАБАРИТ', 'КВТ', 'CТАЦИОНАРНАЯ'];
    private function canSubject($word, $strict = true)
    {
        if (mb_strlen($word) < 3) return false;
        if (false !== $gi = $this->getGramInfo($word)) {
            foreach ($gi as $gii)
                foreach ($gii as $gi0)
                    if ($gi0['pos'] === 'С') {
                        if (false !== array_search('ИМ', $gi0['grammems']) && (!$strict or (false !== array_search('ЕД', $gi0['grammems']))))
                            return true;
                    }
        }
        return false;
    }

    public function getSubject($phrase)
    {
        $res = []; 
        $phrase = str_replace(['Ё', 'ё'], ['Е', 'е'], $phrase);
        
        // предполагаем, что с подлежащего начинается фраза и оно написано с заглавной буквы
        $re = '/\b[А-ЯЁ][а-яё]{2,}\b/u';
        if (false !== preg_match($re, $phrase, $matches)) {
            $word = mb_strtoupper($matches[0]);
            // проверяем наше предположение
            if ($this->canSubject($word, false)) return [$word];
        }

        // не получилось, разбиваем текст на слова
        $words = preg_split('/\b/u', mb_strtoupper($phrase), -1, PREG_SPLIT_NO_EMPTY);

        // Проходим по каждому слову и находим его формы
        foreach ([true, false] as $strict) { // сначала в строгом режиме 
            foreach ($words as $word) {
                if (
                    $this->canSubject($word, $strict) && (false === array_search($word, $res))
                    && (false === array_search($word, $this->stops))
                ) $res[] = $word;
            }
            if (count($res) > 0) break; // если ничего нет, то ищем формы множественного числа
        }

        return $res;
    }
}
