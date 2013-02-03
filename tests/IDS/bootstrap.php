<?php
require_once __DIR__.'/../../vendor/autoload.php';

/** Create temporary directory */
$cacheDir = dirname(__FILE__) . '/../../tmp';
if (!file_exists($cacheDir)) {
    mkdir($cacheDir, 0700);
}

/** Create library cache dir */
$libraryCacheDir = dirname(__FILE__) . '/../../lib/IDS/tmp';
if (!file_exists($libraryCacheDir)) {
    mkdir($libraryCacheDir, 0700);
}
