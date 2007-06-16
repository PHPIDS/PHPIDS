<?php

/**
 * PHP IDS
 *
 * Requirements: PHP5, SimpleXML
 *
 * Copyright (c) 2007 PHPIDS (http://php-ids.org)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 2 of the license.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @version	$Id$
 */

error_reporting(E_ALL | E_STRICT);
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
		$suite = new PHPUnit2_Framework_TestSuite('PHP IDS');
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
