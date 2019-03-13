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

// Set version in files to current git revision
$gitRevision = trim(shell_exec('git rev-parse HEAD'));
foreach (['index.htm', 'help.htm'] as $filename) {
    $fullFilename = "dist/monmap/{$filename}";
    $contents = file_get_contents($fullFilename);
    $contents = str_replace('$VERSION', $gitRevision, $contents);
    file_put_contents($fullFilename, $contents);
}

system("tar -C dist -cvf {$resultFilename} monmap");