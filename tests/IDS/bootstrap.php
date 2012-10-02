<?php
/** Create temporary directory */
$cacheDir = __DIR__ . '/../../tmp';
if (!file_exists($cacheDir)) {
    mkdir($cacheDir, 0700);
}

/** Create library cache dir */
$libraryCacheDir = __DIR__ . '/../../lib/IDS/tmp';
if (!file_exists($libraryCacheDir)) {
    mkdir($libraryCacheDir, 0700);
}
