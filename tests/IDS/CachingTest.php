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

require_once 'PHPUnit/Framework/TestCase.php';
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . '/../../lib');
require_once 'IDS/Init.php';
require_once 'IDS/Caching/CacheFactory.php';

class CachingTest extends \PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->path = dirname(__FILE__) . '/../../lib/IDS/Config/Config.ini.php';
        $this->init = Init::init($this->path);
    }

	function testCachingNone() {
    	$this->init->config['Caching']['caching'] = 'none';
    	$this->assertFalse(Caching\CacheFactory::factory($this->init, 'storage'));
    }

    function testCachingFile() {
        $this->init->config['Caching']['caching'] = 'file';
        $this->init->config['Caching']['expiration_time'] = 0;
        $this->assertTrue(Caching\CacheFactory::factory($this->init, 'storage') instanceof Caching\FileCache);
    }

    function testCachingFileSetCache() {
        $this->init->config['Caching']['caching'] = 'file';
        $this->init->config['Caching']['expiration_time'] = 0;
        $cache = Caching\CacheFactory::factory($this->init, 'storage');
        $cache = $cache->setCache(array(1,2,3,4));
        $this->assertTrue($cache instanceof Caching\FileCache);
    }

    function testCachingFileGetCache() {
        $this->init->config['Caching']['caching'] = 'file';
        $this->init->config['Caching']['path'] =  dirname(__FILE__) . '/../../lib/IDS/tmp/default_filter.cache';
        $this->init->config['Caching']['expiration_time'] = 0;
        $cache = Caching\CacheFactory::factory($this->init, 'storage');
        $cache = $cache->setCache(array(1,2,3,4));
        $this->assertEquals($cache->getCache(), array(1,2,3,4));
    }

    function testCachingSession() {
        $this->init->config['Caching']['caching'] = 'session';
        $this->assertTrue(Caching\CacheFactory::factory($this->init, 'storage') instanceof Caching\SessionCache);
    }

    function testCachingSessionSetCache() {
        $this->init->config['Caching']['caching'] = 'session';

        $cache = Caching\CacheFactory::factory($this->init, 'storage');
        $cache = $cache->setCache(array(1,2,3,4));
        $this->assertTrue($cache instanceof Caching\SessionCache);
    }

    function testCachingSessionGetCache() {
        $this->init->config['Caching']['caching'] = 'session';

        $cache = Caching\CacheFactory::factory($this->init, 'storage');
        $cache = $cache->setCache(array(1,2,3,4));
        $this->assertEquals($cache->getCache(), array(1,2,3,4));
    }

    function testCachingSessionGetCacheDestroyed() {
        $this->init->config['Caching']['caching'] = 'session';

        $cache = Caching\CacheFactory::factory($this->init, 'storage');
        $cache = $cache->setCache(array(1,2,3,4));
        $_SESSION['PHPIDS']['storage'] = null;
        $this->assertFalse($cache->getCache());
    }

    function tearDown() {
    	@unlink(dirname(__FILE__) . '/../../lib/IDS/tmp/default_filter.cache');
    	@unlink(dirname(__FILE__) . '/../../lib/IDS/tmp/memcache.timestamp');
    }
}

/**
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: sw=4 ts=4 expandtab
 */
