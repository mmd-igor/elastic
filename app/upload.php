<?php
namespace Level\VOR;
require 'vendor/autoload.php';
require_once 'config.php';

$arr = json_decode(file_get_contents('/Users/mmd/Downloads/Справочник работ и материалов.json'));
echo count($arr) . PHP_EOL;