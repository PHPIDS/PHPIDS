<?php
/** Create temporary directory */
$cacheDir = __DIR__ . '/../../tmp';
if (!file_exists($cacheDir)) {
    mkdir($cacheDir, 0700);
}
