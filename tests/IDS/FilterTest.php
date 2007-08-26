<?php

/**
 * PHPIDS
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

class IDS_FilterTest extends PHPUnit_Framework_TestCase
	{
	public function testObjectConstruction()
	{
		$filter = new IDS_Filter('^test$', 'My description', array('foo', 'bar'), 12);

		$this->assertTrue($filter->match('test'));
		$this->assertEquals("My description", $filter->getDescription(), "Should return description");
		$this->assertEquals(array("foo", "bar"), $filter->getTags(), "Should return array/list of tags");
		$this->assertEquals('^test$', $filter->getRule());
		$this->assertEquals(12, $filter->getImpact());
	}

	public function testModificator()
	{
		$filter = new IDS_Filter('^te.st$', 'My description', array('tag1', 'tag2'), 1);

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
		$filter = new IDS_Filter('^test$', 'My description', array('foo', 'bar'), 10);

		try {
			$filter->match(1);
			$this->fail("Expected Exception");
		} catch (Exception $e) {}


		try {
			$filter = new IDS_Filter('^test$', 'my desc', array('foo'), 'test');
			$this->fail("Expected Exception");
		} catch (Exception $e) {}

		try {
			$filter = new IDS_Filter(1, 'my desc', array("foo"), 'bla');
			$this->fail("Excpected Exception");
		} catch (Exception $e) {}

	}

	public function testFilterSetFilterSet() {
        $this->storage = new IDS_Filter_Storage();
        if(session_id()) {
            session_destroy();        
        }
        $filter = new IDS_Filter('test', 'test2', array(), 1);
	    $this->assertTrue($this->storage->setFilterSet($filter) instanceof IDS_Filter_Storage);
	}	
	
	public function testFilterGetCache() {
        $this->storage = new IDS_Filter_Storage();
        $this->storage->getFilterFromXML(dirname(__FILE__) . '/../../lib/IDS/default_filter.xml');
        $cache = $this->storage->getCache();	

        $this->assertTrue(is_array($cache));
	}
	
	public function testFilterGetCacheWrongContent() {
        $this->storage = new IDS_Filter_Storage();
        $this->storage->getFilterFromXML(dirname(__FILE__) . '/../../lib/IDS/default_filter.xml');
        $_SESSION['PHPIDS']['Storage'] = null;
        $this->assertFalse($this->storage->getCache());    
    }	
}