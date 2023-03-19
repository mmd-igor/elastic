<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <pre>
<?php

//print_r(explode('.', 'S17.02-33.222.01-D15'));exit;
/*
$sql = <<<eof
'CREATE DATABASE if not exists mw_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER if not exists 'mw_user'@'%' IDENTIFIED BY 'dasljk3JK';
GRANT ALL PRIVILEGES ON mw_db.* TO 'mw_user'@'%' WITH GRANT OPTION;
FLUSH PRIVILEGES;
'
drop table material_work;
drop table works;
drop table wclasses;
drop table materials;
drop table mgroups;

create table if not exists mgroups (
    code varchar(16) primary key, 
    name varchar(128)
);
create table if not exists materials (
    code varchar(32) primary key, 
    name varchar(200), mgroup varchar(16),
    FOREIGN KEY (mgroup) REFERENCES mgroups(code)
);
create table if not exists wclasses (
    code varchar(16) primary key, 
    name varchar(128)
);
create table if not exists works (
    code varchar(16) primary key, 
    name varchar(200), 
    wclass varchar(16),
    FOREIGN KEY (wclass) REFERENCES wclasses(code)
);
create table material_work(
    mcode varchar(32),
    wcode varchar(32),
    primary key(mcode, wcode),
    foreign key (mcode) references materials(code),
    foreign key (wcode) references works(code)
);

mysqldump -u root -p mw_db > mw_db.sql

eof;
*/

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysql = new mysqli("mysql", "mw_user", "dasljk3JK", "mw_db");
$tasks = [
    'mgroups' => ['fields' => ['code' => 2, 'name' => 3]],
    'wclasses' => ['fields' => ['code' => 6, 'name' => 7]],
    'materials' => ['fields' => ['code' => 0, 'name' => 1, 'mgroup' => 2]],
    'works' => ['fields' => ['code' => 4, 'name' => 5, 'wclass' => 6]],
    'material_work' => ['fields' => ['mcode' => 0, 'wcode' => 4]],
    'params' => ['', '', '']
];

foreach ($tasks as $tbl => &$task) {
    if (is_array($task) && key_exists('fields', $task)) {

        $paramscnt = count($task['fields']);

        $sql = "insert into $tbl (";
        $vals = '';
        foreach (array_keys($task['fields']) as $fld) {
            $sql .= $fld . ',';
            $vals .= '?,';
        };
        $sql = rtrim($sql, ',') . ') values (' . rtrim($vals, ',') . ')';
        $task['set'] = $mysql->prepare($sql);

        if ($paramscnt == 2) {
            $task['set']->bind_param('ss', $tasks['params'][0], $tasks['params'][1]);
        } else {
            $task['set']->bind_param('sss', $tasks['params'][0], $tasks['params'][1], $tasks['params'][2]);
        }

        if ($tbl == 'material_work') {
            $task['get'] = $mysql->prepare('select 1 from ' . $tbl . ' where mcode = ? and wcode = ? limit 1');
            $task['get']->bind_param('ss', $tasks['params'][0], $tasks['params'][1]);
        } else {
            $task['get'] = $mysql->prepare('select 1 from ' . $tbl . ' where code = ? limit 1');
            $task['get']->bind_param('s', $tasks['params'][0]);
        }
    }
}


$inputFileName = __DIR__ . '/matworks.csv';

$file = fopen($inputFileName, "r");
$array = [];
if ($file) {
    $cnt = 0;
    while (($buffer = fgetcsv($file, null, ';')) !== false) {
        $cnt++;
        if (!ProcessLine($cnt, $buffer)) break;
    }
}
fclose($file);

function ProcessLine(int $rownum, array $arr): bool
{
    global $tasks;

    if ($rownum == 1) return true;
    //if ($rownum > 3000) return false; //todo
    if (empty($arr[2]) && !empty($arr[0])) {
        $a = explode('.', $arr[0]);
        if (count($a) > 1) $arr[2] = $a[0] . '.' . $a[1]; else return true;
    }
    if (count($arr) != 8) {
        print("line: $rownum - wrong field count\n");
        die(print_r($arr, true));
        return true;
    }

    foreach ($tasks as $tbl => $task) {
        if (is_array($task) && key_exists('fields', $task) && is_array($task['fields'])) {
            $flds = $task['fields'];
            for ($i = 0; $i < count($flds); $i++) {
                $vals = array_values($flds);
                $tasks['params'][$i] = $arr[$vals[$i]];
            }
            $task['get']->execute();
            $task['get']->store_result();
            if ($task['get']->num_rows == 0) {
                $task['set']->execute();
            } elseif ($tbl == 'material_work') {
                //print($rownum . PHP_EOL);
            }
            $task['get']->free_result();
            $task['set']->free_result();
        }
    }
    return true;
}
?>
</pre>

</body>

</html>
