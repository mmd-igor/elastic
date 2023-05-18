<?php
namespace Level\VOR;
require_once __DIR__ . '/../config.php';
require __DIR__ . '/../vendor/autoload.php';

$is_web = php_sapi_name() !== 'cli';
if ($is_web) : ?>

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
endif;

$inputFileName = isset($_GET['file']) ?  __DIR__ . '/../data/' . $_GET['file'] . '.csv' : null;
if (!$inputFileName && $argc > 1) {
    $inputFileName = $argv[1];    
}
if (!$inputFileName || !file_exists($inputFileName)) {
    log("either file not exists or file not defend");
    exit;
}

$elastic = new \Level\VOR\ElasticSearch(ELASTIC_SEARCH_APIKEY, ES_INDEX_MATERIAL);

$file = fopen($inputFileName, "r");
$cnt = 0; $ok = 0;
if ($file) {
    while (false !== $buffer = fgetcsv($file)) {
        $cnt++; if ($cnt == 1) continue;
        if (ProcessLine($cnt, $buffer)) {
            $ok++;
        }
    }
}
fclose($file); $cnt--;
logf("\nTotal: %d Success: %d (%.2f%%)", $cnt, $ok, $ok / $cnt * 100);
log("done.\n");

function ProcessLine(int $rownum, array $arr)
{
    global $elastic;
    $m = $elastic->getMaterial($arr[2], $arr[1], $arr[0]);
    $ok = $m && $m['mcode'] == $arr[3];
    if (!$ok) {
        logf("%3d: %s <> %s \n[%s]\n", $rownum, $arr[3], $m['mcode'], $m['_key']);
    }
    return $ok;
}

if ($is_web) : ?>
</pre>
</body>
</html>
<?php endif;