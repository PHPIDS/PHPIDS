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
 * @version	$Id: FilterTest.php 422 2007-09-01 00:51:21Z mario $
 */

require_once 'PHPUnit/Framework/TestCase.php';
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . '/../../lib');
require_once 'IDS/Init.php';

class IDS_InitTest extends PHPUnit_Framework_TestCase
	{
    function testInit() {
    	$this->assertTrue(IDS_Init::init() instanceof IDS_Init);
    }

    function testInitConfig() {
    	$config = IDS_Init::init();
    	$keys = array('IDS_Basic', 'IDS_Logging', 'IDS_Caching');
    	$this->assertEquals($keys, array_keys($config->config));    	
    }    
}