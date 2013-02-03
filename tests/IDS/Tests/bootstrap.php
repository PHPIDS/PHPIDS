<?php
require_once __DIR__.'/../../../vendor/autoload.php';


$config = array();
foreach ($GLOBALS as $name => $value) {
    if (strpos($name, 'IDS_') !== 0) {
        continue;
    }

    /** Allow environment override */
    if (isset($_SERVER[$name])) {
        $value = $_SERVER[$name];
    }

    /** Make absolute path */
    if (substr($value, 0, 4) === 'lib/') {
        $value = realpath(__DIR__ . '/../../..') . '/' . $value;
    }

    if ($name == 'IDS_TEMP_DIR') {
        $value = $value . '/' . 'IDS_' . microtime(true);
        mkdir($value);
    }

    define($name, $value);
    $config[$name] = $value;
}

$configInfo = <<<EOS
PHPIDS TestSuite configuration:

Filter type:            IDS_FILTER_TYPE
Filter set:             IDS_FILTER_SET
Temporary directory:    IDS_TEMP_DIR
Configuration:          IDS_CONFIG


EOS;

echo str_replace(array_keys($config), array_values($config), $configInfo);
