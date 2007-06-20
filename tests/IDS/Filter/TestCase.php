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
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . '/../../../lib');
require_once "IDS/Filter/Regexp.php";

class IDS_Filter_TestCase extends PHPUnit_Framework_TestCase
	{
	public function testObjectConstruction()
	{
		$filter = new IDS_Filter_Regexp('^test$', 'My description', array('foo', 'bar'), 12);

		$this->assertTrue($filter->match('test'));
		$this->assertEquals("My description", $filter->getDescription(), "Should return description");
		$this->assertEquals(array("foo", "bar"), $filter->getTags(), "Should return array/list of tags");
		$this->assertEquals('^test$', $filter->getRule());
		$this->assertEquals(12, $filter->getImpact());
	}

	public function testExceptions()
	{
		$filter = new IDS_Filter_Regexp('^test$', 'My description', array('foo', 'bar'), 10);

		try {
			$filter->match(1);
			$this->fail("Expected Exception");
		} catch (Exception $e) {}


		try {
			$filter = new IDS_Filter_Regexp('^test$', 'my desc', array('foo'), 'test');
			$this->fail("Expected Exception");
		} catch (Exception $e) {}

		try {
			$filter = new IDS_Filter_Regexp(1, 'my desc', array("foo"), 'bla');
			$this->fail("Excpected Exception");
		} catch (Exception $e) {}

	}

	public function testInvalid()
	{
		
		$this->assertFalse(true);
	}
}
