<?php

chdir(dirname(__FILE__));
if (!file_exists('dist')) {
    mkdir('dist');
}

$resultFilename = 'dist/wvmap.tar.gz';
if (file_exists('dist/wvmap.tar.gz')) {
    unlink($resultFilename);
}

$itemsToPack = [
    'index.htm',
    'help.htm',
    'api.php',
    'ico24',
    'img',
    'lib',
    'locale'
];
$itemsToPackStr = implode(' ', $itemsToPack);
system("tar -cvf {$resultFilename} {$itemsToPackStr}");