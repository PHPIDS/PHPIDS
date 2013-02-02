<?php

/**
 * PHPIDS
 * Requirements: PHP5, SimpleXML
 *
 * Copyright (c) 2010 PHPIDS group (https://phpids.org)
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
 */

namespace IDS;

use IDS\Filter\Storage;

class FilterTest extends \PHPUnit_Framework_TestCase
	{
	public function setUp() {
        $this->path = dirname(__FILE__) . '/../../lib/IDS/Config/Config.ini.php';
        $this->init = Init::init($this->path);
	}

	public function testObjectConstruction()
	{
		$filter = new Filter(1, '^test$', 'My description', array('foo', 'bar'), 12);

		$this->assertTrue($filter->match('test'));
		$this->assertEquals("My description", $filter->getDescription(), "Should return description");
		$this->assertEquals(array("foo", "bar"), $filter->getTags(), "Should return array/list of tags");
		$this->assertEquals('^test$', $filter->getRule());
		$this->assertEquals(12, $filter->getImpact());
	}

	public function testModificator()
	{
		$filter = new Filter(1, '^te.st$', 'My description', array('tag1', 'tag2'), 1);

		// Default must be
		// ... case-insensitive
		$this->assertTrue($filter->match('TE1ST'));
		// ... dot all (\n is matched by .)
		$this->assertTrue($filter->match("TE\nST"));
		// .. "$" is end only #has changed since modifiers are ims
		$this->assertTrue($filter->match("TE1ST\n"));

	}

	public function testExceptions()
	{
		$filter = new Filter(1, '^test$', 'My description', array('foo', 'bar'), 10);

		try {
			$filter->match(1);
			$this->fail("Expected Exception");
		} catch (\Exception $e) {}


		try {
			$filter = new Filter(1, '^test$', 'my desc', array('foo'), 'test');
			$this->fail("Expected Exception");
		} catch (\Exception $e) {}

		try {
			$filter = new Filter(1, 1, 'my desc', array("foo"), 'bla');
			$this->fail("Excpected Exception");
		} catch (\Exception $e) {}

        $this->assertTrue(true, 'Fake assertion so that PHPUnit does not bail');
	}

	public function testFilterSetFilterSet() {

        $this->init->config['General']['filter_type'] = 'xml';
        $this->init->config['General']['filter_path'] = dirname(__FILE__) . '/../../lib/IDS/default_filter.xml';
		$this->storage = new Storage($this->init);
        $filter = array();
		$filter[] = new Filter(1, 'test', 'test2', array(), 1);
	    $this->assertTrue($this->storage->setFilterSet($filter) instanceof Storage);
	}
}

/**
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: sw=4 ts=4 expandtab
 */
