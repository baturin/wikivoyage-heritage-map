<?php

chdir(dirname(__FILE__));

if (file_exists('dist')) {
    system('rm -rf dist');
}

mkdir('dist');
mkdir('dist/monmap');

$resultFilename = 'dist/wvmap.tar.gz';

$itemsToPack = [
    'index.htm',
    'help.htm',
    'api.php',
    'ico24',
    'img',
    'lib',
    'locale'
];

foreach ($itemsToPack as $item) {
    system("cp -r {$item} dist/monmap/{$item}");
}

system("tar -C dist -cvf {$resultFilename} monmap");