<?php

namespace Level\VOR;

use Mysqli;

class works
{
    private $mysql;
    private $stmt;
    private string $m_code = '';

    function __construct()
    {
        $sql = <<<eos
        select mw.wcode as wcode, w.name as wname
            from material_work mw, works w where mw.wcode = w.code and mw.mcode = ?;
        eos;
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $this->mysql = new mysqli("mysql", "mw_user", "dasljk3JK", "mw_db");
        $this->stmt = $this->mysql->prepare($sql);
        $this->stmt->bind_param('s', $this->m_code);
    }

    public function getWork(string $mcode): array|false
    {
        $arr = [];
        $this->m_code = $mcode;
        $this->stmt->execute();
        $result = $this->stmt->get_result();
        if ($result) while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $arr[] = $row;
        }
        $this->stmt->free_result();
        $cnt = count($arr);
        switch ($cnt) {
            case 0:
                return false;
            case 1:
                $arr[0]['_meta']['score'] = WORK_LEVEL_SUCCESS +1;
                break;
            default: {
                if ($cnt >= WORK_LEVEL_SUCCESS) return false;
                $arr[0]['_meta']['score'] = WORK_LEVEL_SUCCESS - $cnt -1;
            }
        }
        $arr[0]['_meta']['method'] = "Q$cnt";
        return $arr[0];
    }

    function __destruct()
    {
        $this->stmt->close();
        $this->mysql->close();
    }
}
