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
    require 'vendor/autoload.php';
    require 'elastic.php';

    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
    $reader->setReadDataOnly(TRUE);
    $spreadsheet = $reader->load("data/spec.xlsx");
    $elastic = new Elastic();

    $worksheet = $spreadsheet->getActiveSheet();

    function getCell($worksheet, $col, $row)
    {
        return $worksheet->getCell($col . $row)->getValue();
    }

    // Get the highest row number and column letter referenced in the worksheet
    $highestRow = $worksheet->getHighestRow(); // 
    $highestColumn = $worksheet->getHighestColumn(); // 
    // Increment the highest column letter
    $highestColumn++;
    ?>
    <table class="table table-hover table-sm">
        <thead class="table-dark">
            <?php for ($row = 1; $row <= $highestRow; ++$row) {
                # ок - количество заполненных полей в строке спецификации
                $ok = 0;
                for ($col = 'A'; $col != $highestColumn; ++$col) if (getCell($worksheet, $col, $row) != null) $ok++;

                if ($ok == 0) continue; # не надо пустых строк

                # сначала перетащим все поля из спецификации
                $rowstr = '';
                for ($col = 'A'; $col != $highestColumn; ++$col) {
                    $rowstr .= sprintf('<td>%s</td>', $worksheet->getCell($col . $row)->getValue());
                }

                # теперь ищем соответствие в справочнике
                $score = 0;
                if ($row >= 2 && $ok > 1) { # только для материалов
                    $ref = $elastic->getRecord($worksheet->getCell('E' . $row)->getValue(), $worksheet->getCell('C' . $row)->getValue(), $worksheet->getCell('B' . $row)->getValue());
                    if ($ref) { # есть что-то?
                        $score =  $ref['_meta']['score'];
                        $rowstr .= sprintf('<td class="" align="right">%.2f</td><td>%s</td>', $score, $ref['material']['raw']);
                    } else
                        $rowstr .= '<td colspan="2" class="red">не найдено</td>';
                } else {
                    $rowstr .= '<td colspan="2"></td>';
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
            }
            ?>
            </tbody>
    </table>

</body>

</html>