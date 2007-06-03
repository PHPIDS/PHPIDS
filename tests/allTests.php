<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE | E_STRICT);
require_once 'PHPUnit2/Framework/TestSuite.php';
require_once 'PHPUnit2/TextUI/TestRunner.php';
class allTests
{
	public static function main()
	{
		PHPUnit2_TextUI_TestRunner::run(self::suite());
	}

	public static function suite()
	{
		$suite = new PHPUnit_Framework_TestSuite('PHP IDS');
		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__FILE__))) as $file) {
			if (substr((string)$file, -4) === '.php') {
				$classname = str_replace('/', '_', preg_replace('#^.*/(IDS/.*)\.php$#', '\1', $file));
				if (substr($classname, 0, 3) === 'IDS') {
					require_once $file;
					$suite->addTestSuite($classname);
				}
			}
		}
		return $suite;
	}
}

