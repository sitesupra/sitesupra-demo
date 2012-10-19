<?php

$q = preg_replace('/^\./', '', $argv[1]);
$q = preg_replace('/.less$/', '', $q);

$cache = false;
$pre = __DIR__ . '/../../../../';

ob_start();
require __DIR__ . '/combo.php';
$data = ob_get_contents();
ob_end_clean();

if (empty($data)) {
echo 'Fail on ', $q, PHP_EOL;
die(255);
}

file_put_contents('.' . $q, $data);


