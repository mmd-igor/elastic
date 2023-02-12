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
            var selects = Array.from(document.getElementsByTagName('select'));
            for (const sel of selects) {
                sel.parentElement.innerText = sel.selectedOptions[0].innerText;
            }
            let t = document.getElementById('mainTable');
            document.getElementById('tableForm').value = t.outerHTML;
        }
        function check_wcode(sel) {
            var idx = sel.selectedIndex;
            var val = sel.options[idx].value;
            document.getElementById('wcode-' + sel.dataset.rownum).innerText = val;
        }
    </script>
</head>

<body>
    <?php
    require_once 'config.php';
    if (false) {
        $elastic = new Elastic();
        $elastic->EsSearch('');
        echo '</body></html>'; 
        exit; 
    }
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

    
    if (array_key_exists('multiLine', $_POST)) {
        $spec->checkMultiRow();
    }
    $greenonly = array_key_exists('greenonly', $_POST);

    //$spec->dump(); die();
    // движок эластика
    $elastic = new Elastic();
    $works = new works();

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
                <td>M-Score</td>
                <td>W-Score</td>
            </tr>
            </tr>
        </thead>
        <tbody>
            <?php
            $okcnt = $corecnt = $successcnt = 0;
            for ($row = 1, $cnt = 0; $row < $spec->count(); ++$row) {

                $item = $spec->getItem($row);
                
                $ccnt = 0;
                foreach($item as $c) if (strlen($c) > 2) $ccnt++;
                // счетчик строк с количеством значимых полей от 3
                if ($ccnt > 2) $corecnt++; elseif ($ccnt == 0) continue; // если нет ни одного поля - не работаем с такой строкой                

                // есть хоть одна из колонок B,C,E со значением длиной более 3 символов?
                $ok = false;
                foreach (['B', 'C', 'E'] as $c) $ok = $ok || (array_key_exists($c, $item) && strlen(trim($item[$c])) > 3);
                // это единственная колонка в строке?
                if ($ok) $ok = $ccnt <= 1;
                if ($ok) { // с такими не работаем - просто копируем строку из спецификации, остальное - пусто и к следующей строке
                    print('<tr class="table-primary"><td></td>');
                    foreach ($header as $c => $h) printf('<td>%s</td>', (array_key_exists($c, $item) ? $item[$c] : ''));
                    print('<td colspan="12"></td></tr>');
                    continue;
                } else $cnt++; // общий счетчик строк спецификации, с которыми работаем

                $rowstr = sprintf('<td>%s</td>', $cnt);
                $notes = [];
                foreach ($header as $l => $h) {
                    $rowstr .= sprintf('<td>%s</td>', (array_key_exists($l, $item) ? $item[$l] : ''));
                }

                $material = @$elastic->getMaterial($item['E'], $item['C'], $item['B']);
                $material_ok = $material && $material['_meta']['score'] >= MATERIAL_LEVEL_SUCCESS;
                if ($material_ok) { $successcnt += 50; } // счетчик успешных распознаваний - 50 очков за материал
                if ($greenonly && (!$material || ($material && $material['_meta']['score'] < MATERIAL_LEVEL_SUCCESS))) {
                    echo '<tr>' . $rowstr;
                    for ($i = 0; $i < 12; $i++) echo '<td></td>';
                    echo '</tr>';
                    continue;
                }

                if ($material && (!$greenonly || $material_ok)) {
                    $excode = array_key_exists('excode', $_POST) ? $_POST['excode'] : '';
                    //$work = @$elastic->getWork2($material['vcode']['raw'], $item['B'], $excode);
                    $work = $works->getWork($material['mcode']['raw']);
                    if ($material_ok && $excode != '' && is_array($work)) $work['_meta']['score'] += 3.0;
                } else {
                    $work = null;
                }
                if ($material_ok && $work && $work['_meta']['score'] >= WORK_LEVEL_SUCCESS) { $successcnt += 50; } // счетчик успешных распознаваний - 50 очков за работу
                if ($greenonly && (!$work || ($work && $work['_meta']['score'] < WORK_LEVEL_SUCCESS))) {
                    for ($i = 0; $i < 3; $i++) $rowstr .= '<td></td>';
                } else {
                    if ($work) {
                        $s = $work['_meta']['score'];
                        if ($s < 7) $c = 'danger';
                        else if ($s < WORK_LEVEL_SUCCESS) $c = 'warning';
                        else $c = 'success';
                        foreach (['excode', 'wcode'] as $l) $rowstr .= sprintf('<td class="table-%s" id="%s-%d">%s</td>', $c, $l, $cnt, (array_key_exists($l, $work) ? (is_array($work[$l]) ? $work[$l]['raw'] : $work[$l]) : ''));
                        if (count($work['rows']) > 1) {
                            $rowstr .= sprintf('<td class="table-%s"><select name="works" data-rownum="%d" onchange="check_wcode(this);">', $c, $cnt);
                            foreach ($work['rows'] as $wr) {
                                $rowstr .= sprintf('<option value="%s">%s</option>', $wr['wcode'], $wr['wname']);
                            }
                            $rowstr .= '</select></td>';
                        } else {
                            $rowstr .= sprintf('<td class="table-%s">%s</td>', $c, (array_key_exists('wname', $work) ? (is_array($work['wname']) ? $work['wname']['raw'] : $work['wname']) : ''));
                        }
                        $notes[] = sprintf('w%.0f/%s', $work['_meta']['score'], $work['_meta']['method']);
                    } else {
                        $rowstr .= '<td colspan="3" class="table-danger">работ не найдено</td>';
                    }
                }
                //
                if ($material) {
                    $s = $material['_meta']['score'];
                    if ($s < 100) $c = 'danger';
                    else if ($s < MATERIAL_LEVEL_SUCCESS) $c = 'warning';
                    else $c = 'success';
                    foreach (['gcode', 'group', 'mcode', 'material'] as $l) $rowstr .= sprintf('<td class="table-%s">%s</td>', $c, (array_key_exists($l, $material) ? $material[$l]['raw'] : ''));
                    $notes[] = sprintf('m%.0f/%s', $material['_meta']['score'], $material['_meta']['method']);
                } else
                    $rowstr .= '<td colspan="4" class="table-danger">материал не найден</td>';
                //
                $rowstr .= sprintf('<td>%s</td>', implode(', ', $notes));
                $rowstr .= sprintf('<td></td><td></td><td>%s</td><td>%s</td>', $material ? round($material['_meta']['score']) : '-', $work ? round($work['_meta']['score']) : '-');
                printf("<tr>%s</tr>\n", $rowstr);
            }
            unset($worksheet);
            unset($spreadsheet);
            unset($reader);
            unlink($uploadfile);
            ?>
        </tbody>
    </table>
    <div>Качество рапознавания: <?= round($successcnt / $corecnt) ?>%</div>
    <div class="mx-auto col-1 my-3">
        <form enctype="multipart/form-data" action="/saveAs.php" method="POST" onsubmit="saveToExcel()">
            <input type="hidden" name="table" id="tableForm" />
            <button type="submit" class="btn btn-primary">Save as Excel...</button>
        </form>
    </div>
</body>

</html>