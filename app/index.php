<?php //phpinfo(); exit; 
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
                        <input type="submit" class="btn btn-outline-secondary" value="Отправить" />
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="multiLine" value="true" name="multiLine">
                        <label class="form-check-label" for="multiLine">Многострочный парсер</label>
                    </div>
                </form>
            </div>
        </div>

    <?php
        echo '</body></html>';
        exit;
    }

    require 'vendor/autoload.php';
    require 'elastic.php';
    require 'specification.php';

    //echo '<pre>';     print_r($_POST);     echo '</pre>';     exit;

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
    //$spec->dump();     exit;

    // движок эластика
    $elastic = new Elastic();

    ?>
    <h1>Ведомость объема работ (ВОР)</h1>
    <table class="table table-hover table-sm">
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
            for ($row = 1; $row < $spec->count(); ++$row) {

                $item = $spec->getItem($row);

                $ok = false;
                foreach (['B', 'C', 'E'] as $c) $ok = $ok || (array_key_exists($c, $item) && strlen($item[$c]) > 3);
                if ($ok) $ok = count($item) > 1;
                if (!$ok) {
                    print('<tr class="table-primary">');
                    foreach ($header as $c => $h) printf('<td>%s</td>', (array_key_exists($c, $item) ? $item[$c] : ''));
                    print('<td colspan="11"></td><tr>');
                    continue;
                }

                $material = @$elastic->getRecord($item['E'], $item['C'], $item['B'], 'level-engine');
                $work = @$elastic->getRecord(null, ($material ? $material['vcode']['raw'] : null), $item['B'], 'works');

                $rowstr = sprintf('<td>%s</td>', $row);
                $notes = [];
                foreach ($header as $l => $h) {
                    $rowstr .= sprintf('<td>%s</td>', (array_key_exists($l, $item) ? $item[$l] : ''));
                }
                if ($work) {
                    $s = $work['_meta']['score'];
                    if ($s < 7) $c = 'danger';
                    else if ($s < 10) $c = 'warning';
                    else $c = 'success';
                    foreach (['excode', 'wcode', 'wname'] as $l) $rowstr .= sprintf('<td class="table-%s">%s</td>', $c, (array_key_exists($l, $work) ? $work[$l]['raw'] : ''));
                    $notes[] = sprintf('w%.0f/%s', $work['_meta']['score'], $work['_meta']['method']);
                } else
                    $rowstr .= '<td colspan="3" class="table-danger">работ не найдено</td>';
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
                $rowstr .= '<td colspan="2"></td>';
                printf("<tr>%s</tr>\n", $rowstr);
            }
            /*
                $rowstr = '';
                foreach($header as $hdr) $rowstr .= sprintf('<td>%s</td>', $hdr);
                $score = 0;
                if ($row >= 2 && $ok > 1) { # только для материалов
                    $ref = $elastic->getRecord($worksheet->getCell('E' . $row)->getValue(), $worksheet->getCell('C' . $row)->getValue(), $worksheet->getCell('B' . $row)->getValue());
                    if ($ref) { # есть что-то?
                        $score =  $ref['_meta']['score'];
                        $rowstr .= sprintf('<td class="fw-bold text-end">%.2f</td><td class="text-nowrap">%s</td><td>%s</td>', $score, $ref['mcode']['raw'], $ref['material']['raw']);
                    } else
                        $rowstr .= '<td colspan="3" class="red">не найдено</td>';
                } else {
                    $rowstr .= '<td colspan="3"></td>';
                }

                # боевая раскраска
                if ($row == 1) $class = 'dark'; # шапка таблицы
                else if ($ok == 1) $class = 'primary'; # секции (группы) спецификации
                else if ($score < 100) $class = 'danger';
                else if ($score < 200) $class = 'warning';
                else $class = 'success';

                #
                printf('<tr class="table-%s">', $class);
                echo $rowstr;
                echo '</tr>' . PHP_EOL;

                # структура таблицы
                if ($row == 1) echo '</thead><tbody class="table-group-divider">';
                */

            unset($worksheet);
            unset($spreadsheet);
            unset($reader);
            unlink($uploadfile);
            ?>
        </tbody>
    </table>

</body>

</html>