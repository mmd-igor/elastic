<?php

namespace Level\VOR;

require 'vendor/autoload.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link href="/assets/styles.css?ver=1" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <script>
        function saveToExcel() {
            let t = document.getElementById('mainTable');
            document.getElementById('tableForm').value = t.outerHTML;
        }
    </script>
</head>

<body>
    <?php
    //
    if (count($_FILES) > 0) {
        $uploaddir = '/var/www/data/';
        $uploadfile = $uploaddir . basename($_FILES['userfile']['name']);

        if (!move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
            die("Possible file upload attack!\n");
        }
        // файл закачан, он в $uploadfile
    } else {
        // upload html-form
    ?>
        <div class="row" id="uploader">
            <div class="d-flex justify-content-center">
                <form enctype="multipart/form-data" action="/" method="POST">
                    <label for="formFile" class="form-label">Закачайте сюда файл спецификации</label>
                    <div class="input-group mb-3">
                        <!-- <input class="form-control" type="file" id="formFile" name="userfile" /> -->
                        <input accept=".xlsx,.xls" class="form-control" type="file" id="formFile" name="userfile" />
                        <input class="form-control" type="text" placeholder="Фильтр по коду затрат" aria-label="" name="excode">
                        <input type="submit" class="btn btn-outline-secondary" value="Отправить" />
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="multiLine" value="true" name="multiLine">
                        <label class="form-check-label" for="multiLine">Многострочный парсер</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="greenonly" value="true" name="greenonly">
                        <label class="form-check-label" for="greenonly">Только зеленые</label>
                    </div>
                </form>
            </div>
        </div>

    <?php
        echo '</body></html>';
        exit;
    }

    require_once 'config.php';

    try {
        // загрузка спецификации
        $spec = new Specification($uploadfile);
        //
    } catch (\Throwable $th) { ?>
        <div class="alert alert-danger" role="alert">
            <span>похоже, это не тот файл, что нам нужен</span>
            <button type="button" class="btn btn-primary" onclick="location.href='/'">Назад</button>
        </div>
    <?php
        unlink($uploadfile);
        die('');
    }

    if (array_key_exists('multiLine', $_POST)) $spec->checkMultiRow();
    $greenonly = array_key_exists('greenonly', $_POST);

    // движок эластика
    $elastic = new Elastic();
    //$elastic->EsSearch('');     exit;

    ?>
    <h1>Ведомость объема работ (ВОР)</h1>
    <table class="table table-hover table-sm" id="mainTable">
        <thead class="table-dark">
            <tr>
                <td>#</td>
                <?php
                $header = $spec->getHeader();
                foreach ($header as $h) printf('<td>%s</td>', $h);
                ?>
                <td>Код статьи затрат</td>
                <td>Код работы</td>
                <td>Наименование работы</td>
                <td>Код группы материалов</td>
                <td>Наименование группы материалов</td>
                <td>Код материала</td>
                <td>Наименование материала</td>
                <td>Примечание</td>
                <td>Ед.изм</td>
                <td>Объем</td>
            </tr>
            </tr>
        </thead>
        <tbody>
            <?php
            for ($row = 1, $cnt = 0; $row < $spec->count(); ++$row) {

                $item = $spec->getItem($row);

                $ok = false;
                foreach (['B', 'C', 'E'] as $c) $ok = $ok || (array_key_exists($c, $item) && strlen($item[$c]) > 3);
                if ($ok) $ok = count($item) > 1;
                if (!$ok) {
                    print('<tr class="table-primary"><td></td>');
                    foreach ($header as $c => $h) printf('<td>%s</td>', (array_key_exists($c, $item) ? $item[$c] : ''));
                    print('<td colspan="10"></td><tr>');
                    continue;
                } else $cnt++;

                $rowstr = sprintf('<td>%s</td>', $cnt);
                $notes = [];
                foreach ($header as $l => $h) {
                    $rowstr .= sprintf('<td>%s</td>', (array_key_exists($l, $item) ? $item[$l] : ''));
                }

                $material = @$elastic->getMaterial($item['E'], $item['C'], $item['B']);
                if ($greenonly && (!$material || ($material && $material['_meta']['score'] < 200))) {
                    echo '<tr>' . $rowstr;
                    for ($i = 0; $i < 10; $i++) echo '<td></td>';
                    echo '</tr>';
                    continue;
                }

                $work = @$elastic->getWork(($material ? $material['vcode']['raw'] : null), $item['B'], (array_key_exists('excode', $_POST) ? $_POST['excode'] : ''));
                if ($greenonly && (!$work || ($work && $work['_meta']['score'] < 10))) {
                    for ($i = 0; $i < 3; $i++) $rowstr .= '<td></td>';
                } else {
                    if ($work) {
                        $s = $work['_meta']['score'];
                        if ($s < 7) $c = 'danger';
                        else if ($s < 10) $c = 'warning';
                        else $c = 'success';
                        foreach (['excode', 'wcode', 'wname'] as $l) $rowstr .= sprintf('<td class="table-%s">%s</td>', $c, (array_key_exists($l, $work) ? $work[$l]['raw'] : ''));
                        $notes[] = sprintf('w%.0f/%s', $work['_meta']['score'], $work['_meta']['method']);
                    } else {
                        $rowstr .= '<td colspan="3" class="table-danger">работ не найдено</td>';
                    }
                }
                //
                if ($material) {
                    $s = $material['_meta']['score'];
                    if ($s < 100) $c = 'danger';
                    else if ($s < 200) $c = 'warning';
                    else $c = 'success';
                    foreach (['gcode', 'group', 'mcode', 'material'] as $l) $rowstr .= sprintf('<td class="table-%s">%s</td>', $c, (array_key_exists($l, $material) ? $material[$l]['raw'] : ''));
                    $notes[] = sprintf('m%.0f/%s', $material['_meta']['score'], $material['_meta']['method']);
                } else
                    $rowstr .= '<td colspan="4" class="table-danger">материал не найден</td>';
                //
                $rowstr .= sprintf('<td>%s</td>', implode(', ', $notes));
                $rowstr .= '<td></td><td></td>';
                printf("<tr>%s</tr>\n", $rowstr);
            }
            unset($worksheet);
            unset($spreadsheet);
            unset($reader);
            unlink($uploadfile);
            ?>
        </tbody>
    </table>
    <div class="mx-auto col-1 my-3">
        <form enctype="multipart/form-data" action="/saveAs.php" method="POST" onsubmit="saveToExcel()">
            <input type="hidden" name="table" id="tableForm" />
            <button type="submit" class="btn btn-primary">Save as Excel...</button>
        </form>
    </div>
</body>

</html>