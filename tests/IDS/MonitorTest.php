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

require_once 'PHPUnit/Framework/TestCase.php';
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . '/../../lib');
require_once 'IDS/Monitor.php';
require_once 'IDS/Filter/Storage.php';

class IDS_MonitorTest extends PHPUnit_Framework_TestCase {

	public function setUp()
	{
		$this->storage = new IDS_Filter_Storage();
		$this->storage->getFilterFromXML(dirname(__FILE__) . '/../../lib/default_filter.xml');
	}

	public function testRunWithTags() {
		$test = new IDS_Monitor(
			array('user' => 'admin<script/src=http/attacker.com>'),
			$this->storage,
			array('csrf')
		);

		$result = $test->run();

		foreach ($result->getEvent('user')->getFilters() as $filter) {
			$this->assertTrue(in_array('csrf', $filter->getTags()));
		}
	}

	public function testRun() {
		$test = new IDS_Monitor(
			array(
				'id' 	=> '9<script/src=http/attacker.com>',
				'name' 	=> '" style="-moz-binding:url(http://h4k.in/mozxss.xml#xss);" a="'
			),
			$this->storage
		);

		$result = $test->run();

		$this->assertTrue($result->hasEvent('id'));
		$this->assertTrue($result->hasEvent('name'));
	}

	public function testNoResult() {
		$test = new IDS_Monitor(array('test', 'bla'), $this->storage);
		$this->assertTrue($test->run()->isEmpty());
	}

	public function testList()
	{
		$test = new IDS_Monitor(
			array('9<script/src=http/attacker.com>', '" style="-moz-binding:url(http://h4k.in/mozxss.xml#xss);" a="'),
			$this->storage
		);
		$result = $test->run();
		$this->assertTrue($result->hasEvent(1));
		$this->assertEquals(6, $result->getImpact());
	}

    public function testXSSList() {
        $test = new IDS_Monitor(
            array('\'\'"--><script>eval(String.fromCharCode(88,83,83)));%00', '"></a style="xss:ex/**/pression(alert(1));"'),
            $this->storage
        );
        $result = $test->run();
        $this->assertTrue($result->hasEvent(1));
        $this->assertEquals(22, $result->getImpact());        
    }

    public function testSQLIList() {
        $test = new IDS_Monitor(
            array('" OR 1=1#', '; DROP table Users --'),
            $this->storage
        );
        $result = $test->run();
        $this->assertTrue($result->hasEvent(1));
        $this->assertEquals(8, $result->getImpact());        
    }
    
    public function testDTList(){
        $test = new IDS_Monitor(
            array('../../etc/passwd', '\%windir%\cmd.exe'),
            $this->storage
        );
        $result = $test->run();
        $this->assertTrue($result->hasEvent(1));
        $this->assertEquals(5, $result->getImpact());        
    }
    
    public function testRFEList() {
        $test = new IDS_Monitor(
            array(';phpinfo()', '"; <?php exec("rm -rf /"); ?>'),
            $this->storage
        );
        $result = $test->run();
        $this->assertTrue($result->hasEvent(1));
        $this->assertEquals(20, $result->getImpact());       
    }
    
    public function testDecimalCCConverter() {
        $test = new IDS_Monitor(
            array('XXX', '60,115,99,114,105,112,116,62,97,108,101,114,116,40,49,41,60,47,115,99,114,105,112,116,62'),
            $this->storage
        );
        $result = $test->run();
        $this->assertTrue($result->hasEvent(1));
        $this->assertEquals(15, $result->getImpact());              
    }

    public function testOctalCCConverter() {
        $test = new IDS_Monitor(
            array('XXX', '\74\163\143\162\151\160\164\76\141\154\145\162\164\50\47\150\151\47\51\74\57\163\143\162\151\160\164\76'),
            $this->storage
        );
        $result = $test->run();
        $this->assertTrue($result->hasEvent(1));
        $this->assertEquals(34, $result->getImpact());              
    }

    public function testHexCCConverter() {

        $test = new IDS_Monitor(
            array('XXX', '\x0000003c\x0000073\x0000063\x0000072\x0000069\x0000070\x0000074\x000003e\x0000061\x000006c\x0000065\x0000072\x0000074\x0000028\x0000032\x0000029\x000003c\x000002f\x0000073\x0000063\x0000072\x0000069\x0000070\x0000074\x000003e'),
            $this->storage
        );
        $result = $test->run();
        $this->assertTrue($result->hasEvent(1));
        $this->assertEquals(22, $result->getImpact());              
    }
}
