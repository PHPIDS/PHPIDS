<?php

/**
 * PHPIDS
 * Requirements: PHP5, SimpleXML
 *
 * Copyright (c) 2007 PHPIDS group (http://php-ids.org)
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
 * @package	PHPIDS tests
 * @version	SVN: $Id:allTests.php 515 2007-09-15 13:43:40Z christ1an $
 */

error_reporting(E_ALL | E_STRICT);
require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'PHPUnit/TextUI/TestRunner.php';
require_once 'PHPUnit/Extensions/ExceptionTestCase.php';
class allTests
{
	public static function main()
	{
		PHPUnit_TextUI_TestRunner::run(self::suite());
	}

	public static function suite()
	{
        $suite = new PHPUnit_Framework_TestSuite('PHPIDS');

        /**
        $files = scandir(dirname(__FILE__) . '/IDS');
        foreach ($files as $file) {
            if (is_file('IDS/' . $file)) {
                require_once 'IDS/' . $file;

                $class = substr($file, 0, (strlen($file) - 4));
                $class = 'IDS_' . $class;
                $suite->addTestSuite($class);
            }
        }*/

        require_once 'IDS/MonitorTest.php';
        require_once 'IDS/ReportTest.php';
        require_once 'IDS/InitTest.php';
        require_once 'IDS/ExceptionTest.php';
        require_once 'IDS/FilterTest.php';
        require_once 'IDS/CachingTest.php';
        require_once 'IDS/EventTest.php';

        $suite->addTestSuite('IDS_MonitorTest');
        $suite->addTestSuite('IDS_ReportTest');
        $suite->addTestSuite('IDS_InitTest');
        $suite->addTestSuite('IDS_ExceptionTest');
        $suite->addTestSuite('IDS_FilterTest');
        $suite->addTestSuite('IDS_CachingTest');
        $suite->addTestSuite('IDS_EventTest');

        return $suite;

	}
}
