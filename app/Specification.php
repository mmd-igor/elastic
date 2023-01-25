<?php
namespace Level\VOR;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

function array_merge_grow(array $a1, array $a2): array
{
    $res = $a1;
    foreach ($a2 as $i => $v) {
        if (array_key_exists($i, $res))
            $res[$i] .= ' ' . $v;
        else
            $res[$i] = $v;
    }
    return $res;
}

class Specification
{
    public $items = [];

    function __destruct()
    {
        unset($this->items);
    }

    private function checkState($inrow, $row, $idx): bool
    {
        
        $stop = count($row) == 1; $idx++;
        if ($stop && $idx < count($this->items)) $stop = array_key_exists('A', $this->getItem($idx));
        return array_key_exists('A', $row) != $inrow && ($stop == false);
        /*
        $isA = array_key_exists('A', $row) && $row['A'] !== '';// $this->getItem($idx));
        $onlyB = (count($row) == 1) && array_key_exists('B', $row) && $row['B'] !== '';
        if ($inrow) {
            if ($onlyB) {
                return true;
            }
            else {
                return false;
            }

        } else {
            if ($isA) {
                return true;
            }
            else {
                return false;
            }
        }
        */
    }

    public function checkMultiRow()
    {
        $cnt = count($this->items);
        if ($cnt <= 1) return;
        //
        $res = [];
        $newrow = [];
        $inrow = false;
        $newstate = false;
        foreach ($this->items as $i => $row) {
            if ($i == 0) { // заголовок
                $res[] = $row;
                continue;
            }
            $newstate = $this->checkState($inrow, $row, $i);
            //print_r($row); printf("%s -> %s [%d cnt:%d]<br>", (int)$inrow, (int)$newstate, $i, count($row));
            if ($inrow) {
                if ($newstate) $newrow = array_merge_grow($newrow, $row);
                else {
                    $res[] = $newrow;
                    $newrow = $row;
                    $newstate = true;
                }
            } else {
                if ($newstate) {
                    if (count($newrow) > 0) $res[] = $newrow;
                    $newrow = $row;
                } else $res[] = $row;
            }
            $inrow = $newstate;
        }
        if (count($newrow) > 0) $res[] = $newrow;
        $this->items = $res;
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
            //if ($row == 1) $arr = []; else $arr['corecnt'] = 0;
            $arr = [];
            for ($col = 'A'; $col != $highestColumn; ++$col) {
                $value = $worksheet->getCell($col . $row)->getValue();
                if ($value != null) {
                    $value = trim($value);
                    if ($value !== '') {
                        $arr[$col] = $value;
                        //if ($row > 1 && strlen($value) > 2) $arr['corecnt']++;
                    }
                }
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
        if (count($this->items) >= $idx) return $this->items[$idx];
        else return [];
    }
    public function getHeader(): array
    {
        return $this->getItem(0);
    }
}
