<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class Specification
{
    public $items = [];

    function __destruct()
    {
        unset($this->items);
    }

    private function checkMultiRow()
    {
        /*
        $res = [];
        $cnt = count($this->items);
        if ($cnt <= 1) return;
        //
        foreach ($this->items as $i => $row) {
            if ($i == 0 // заголовок
            || count($row) == 1 // раздел или подраздел
            ) $res[] = $row;
            else {
               if (array_key_exists('A', $row) && count($row) > 1) $res[] = $this->joinMultiRow($i);
            }
        }

        $inrow = 0;
        for ($i = 1; $i < $cnt; ++$i) {
            if ($inrow == 0) {
                if (count($this->items[$i]) > 1 && array_key_exists('A', $this->items[$i]))

            } else {

            }

        }
        */
    }

    function __construct($fname)
    {

        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(TRUE);
            $spreadsheet = $reader->load($fname);

        $worksheet = $spreadsheet->getActiveSheet();

        // Get the highest row number and column letter referenced in the worksheet
        $highestRow = $worksheet->getHighestRow(); //
        $highestColumn = $worksheet->getHighestColumn(); //
        // Increment the highest column letter
        $highestColumn++;
        //
        for ($row = 1; $row <= $highestRow; ++$row) {
            $arr = [];
            for ($col = 'A'; $col != $highestColumn; ++$col) {
                $value = $worksheet->getCell($col . $row)->getValue();
                if ($value != null) $arr[$col] = $value;
            }
            if (count($arr) > 0) $this->items[] = $arr;
        }
        unset($worksheet);
        unset($spreadsheet);
        unset($reader);
    }

    public function dump()
    {
        echo '<pre>';
        print_r($this->items);
        echo '</pre>';
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getItem(int $idx): array
    {
        if (count($this->items) >= $idx) return $this->items[$idx]; else return [];
    }
    public function getHeader(): array
    {
        return $this->getItem(0);
    }
    
}

