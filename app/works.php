<?php

namespace Level\VOR;

use Mysqli;

class works
{
    private $mysql;
    private $stmt;
    private string $m_code = '';
    private string $m_excode = '';

    function __construct(string $excode = '')
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $this->mysql = new mysqli("mysql", "mw_user", "dasljk3JK", "mw_db");

        $this->m_excode = trim($excode) . '%';
        $sql = <<<eos
        select mw.wcode as wcode, w.name as wname, w.wclass as excode
            from material_work mw, works w where mw.wcode = w.code and mw.mcode = ?
        eos;
        if ($this->m_excode !== '') $sql .= ' and w.wclass like ?';

        $this->stmt = $this->mysql->prepare($sql);

        if ($this->m_excode === '')
            $this->stmt->bind_param('s', $this->m_code);
        else
            $this->stmt->bind_param('ss', $this->m_code, $this->m_excode);
    }

    public function getWork(string $mcode, string $excode = '')
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
                $arr[0]['_meta']['score'] = WORK_LEVEL_SUCCESS + 1;
                break;
            default: {
                    if ($cnt >= WORK_LEVEL_SUCCESS) return false;
                    $arr[0]['_meta']['score'] = WORK_LEVEL_SUCCESS - $cnt - 1;
                    $arr[0]['rows'] = $arr;
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
?>
