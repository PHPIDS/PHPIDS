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
 * @version	SVN: $Id:CachingTest.php 515 2007-09-15 13:43:40Z christ1an $
 */

require_once 'PHPUnit/Framework/TestCase.php';
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . '/../../lib');
require_once 'IDS/Init.php';
require_once 'IDS/Caching/Factory.php';

class IDS_CachingTest extends PHPUnit_Framework_TestCase {
    
	function testCachingNone() {
		$config = IDS_Init::init('IDS/Config/Config.ini');
    	$config->config['IDS_Caching']['caching'] = 'none';
    	$this->assertFalse(IDS_Caching::factory($config->config['IDS_Caching'], 'storage'));
    }  

    function testCachingFile() {
        $config = IDS_Init::init('IDS/Config/Config.ini');
        $config->config['IDS_Caching']['caching'] = 'file';
        $config->config['IDS_Caching']['expiration_time'] = 0;
        $this->assertTrue(IDS_Caching::factory($config->config['IDS_Caching'], 'storage') instanceof IDS_Caching_File);
    } 

    function testCachingFileSetCache() {
        $config = IDS_Init::init('IDS/Config/Config.ini');
        $config->config['IDS_Caching']['caching'] = 'file';
        $config->config['IDS_Caching']['expiration_time'] = 0;
        $cache = IDS_Caching::factory($config->config['IDS_Caching'], 'storage');
        $cache = $cache->setCache(array(1,2,3,4));
        $this->assertTrue($cache instanceof IDS_Caching_File);
    }    

    function testCachingFileGetCache() {
        $config = IDS_Init::init('IDS/Config/Config.ini');
        $config->config['IDS_Caching']['caching'] = 'file';
        $config->config['IDS_Caching']['path'] =  dirname(__FILE__) . '/../../lib/IDS/tmp/default_filter.cache';
        $config->config['IDS_Caching']['expiration_time'] = 0;
        $cache = IDS_Caching::factory($config->config['IDS_Caching'], 'storage');
        $cache = $cache->setCache(array(1,2,3,4));
        $this->assertEquals($cache->getCache(), array(1,2,3,4));
    }     

    function testCachingSession() {
        $config = IDS_Init::init('IDS/Config/Config.ini');
        $config->config['IDS_Caching']['caching'] = 'session';
        $this->assertTrue(IDS_Caching::factory($config->config['IDS_Caching'], 'storage') instanceof IDS_Caching_Session);
    }     

    function testCachingSessionSetCache() {
        $config = IDS_Init::init('IDS/Config/Config.ini');
        $config->config['IDS_Caching']['caching'] = 'session';
        
        $cache = IDS_Caching::factory($config->config['IDS_Caching'], 'storage');
        $cache = $cache->setCache(array(1,2,3,4));
        $this->assertTrue($cache instanceof IDS_Caching_Session);
    }    

    function testCachingSessionGetCache() {
        $config = IDS_Init::init('IDS/Config/Config.ini');
        $config->config['IDS_Caching']['caching'] = 'session';
        
        $cache = IDS_Caching::factory($config->config['IDS_Caching'], 'storage');
        $cache = $cache->setCache(array(1,2,3,4));
        $this->assertEquals($cache->getCache(), array(1,2,3,4));
    } 

    function testCachingSessionGetCacheDestroyed() {
        $config = IDS_Init::init('IDS/Config/Config.ini');
        $config->config['IDS_Caching']['caching'] = 'session';
        
        $cache = IDS_Caching::factory($config->config['IDS_Caching'], 'storage');
        $cache = $cache->setCache(array(1,2,3,4));
        $_SESSION['PHPIDS']['storage'] = null;        
        $this->assertFalse($cache->getCache());
    }    

    function tearDown() {
    	@unlink(dirname(__FILE__) . '/../../lib/IDS/tmp/default_filter.cache');
    	@unlink(dirname(__FILE__) . '/../../lib/IDS/tmp/memcache.timestamp');
    }
}
