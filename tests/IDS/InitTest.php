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
 * @version	SVN: $Id:InitTest.php 517 2007-09-15 15:04:13Z mario $
 */

require_once 'PHPUnit/Framework/TestCase.php';
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . '/../../lib');
require_once 'IDS/Init.php';

class IDS_InitTest extends PHPUnit_Framework_TestCase
	{
    function testInit() {
    	$this->assertTrue(IDS_Init::init('IDS/Config/Config.ini') instanceof IDS_Init);
    }

    function testInitConfig() {
    	$config = IDS_Init::init('IDS/Config/Config.ini');
    	$keys = array('General', 'Logging', 'Caching');
    	$this->assertEquals($keys, array_keys($config->config));    	
    }  

    function testInitClone() {
        $config = IDS_Init::init('IDS/Config/Config.ini');
        $config2 = clone $config;
        $this->assertEquals($config2, $config);        
    }  
    
    function testInitGetConfigPath() {
        $config = IDS_Init::init('IDS/Config/Config.ini');
        $this->assertEquals($config->getConfigPath(), 'IDS/Config/Config.ini');	
    }
    
    function testInitSetConfigOverwrite() {
        $config = IDS_Init::init('IDS/Config/Config.ini');
        $config->setConfig(array('General' => array('filter_type' => 'json')), true);
        $this->assertEquals($config->config['General']['filter_type'], 'json'); 
    }

	function testInitSetConfigNoOverwrite() {
        $config = IDS_Init::init('IDS/Config/Config.ini');
        $config->setConfig(array('General' => array('filter_type' => 'xml')), true);
        $config->setConfig(array('General' => array('filter_type' => 'json')));
        $this->assertEquals($config->config['General']['filter_type'], 'xml'); 
    }    
    
	function testInitGetConfig() {
        $config = IDS_Init::init('IDS/Config/Config.ini');
        $data = $config->getConfig();
        $this->assertEquals($config->config, $data); 
    }
}
