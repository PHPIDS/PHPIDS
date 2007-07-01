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
require_once "IDS/Filter/Regex.php";

class IDS_FilterTest extends PHPUnit_Framework_TestCase
	{
	public function testObjectConstruction()
	{
		$filter = new IDS_Filter_Regex('^test$', 'My description', array('foo', 'bar'), 12);

		$this->assertTrue($filter->match('test'));
		$this->assertEquals("My description", $filter->getDescription(), "Should return description");
		$this->assertEquals(array("foo", "bar"), $filter->getTags(), "Should return array/list of tags");
		$this->assertEquals('^test$', $filter->getRule());
		$this->assertEquals(12, $filter->getImpact());
	}

	public function testModificator()
	{
		$filter = new IDS_Filter_Regex('^te.st$', 'My description', array('tag1', 'tag2'), 1);

		// Default must be 
		// ... case-insensitive
		$this->assertTrue($filter->match('TE1ST'));
		// ... dot all (\n is matched by .)
		$this->assertTrue($filter->match("TE\nST"));
		// .. "$" is end only
		$this->assertFalse($filter->match("TE1ST\n"));

		// Change it to the opposite
		IDS_Filter_Regex::setFlags('');
		// Must fail because of case-sensitivity
		$this->assertFalse($filter->match('TE1ST'));
		// Must fail because "." does not match "\n"
		$this->assertFalse($filter->match("TE\nST"));
		// Must pass because $ means end of line or newline
		$this->assertTrue($filter->match("te1st\n"));
	}

	public function testExceptions()
	{
		$filter = new IDS_Filter_Regex('^test$', 'My description', array('foo', 'bar'), 10);

		try {
			$filter->match(1);
			$this->fail("Expected Exception");
		} catch (Exception $e) {}


		try {
			$filter = new IDS_Filter_Regex('^test$', 'my desc', array('foo'), 'test');
			$this->fail("Expected Exception");
		} catch (Exception $e) {}

		try {
			$filter = new IDS_Filter_Regex(1, 'my desc', array("foo"), 'bla');
			$this->fail("Excpected Exception");
		} catch (Exception $e) {}

	}
}
