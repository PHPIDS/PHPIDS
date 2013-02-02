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

use IDS\Caching\CacheFactory;
use IDS\Caching\FileCache;
use IDS\Caching\SessionCache;

class CachingTest extends \PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->path = dirname(__FILE__) . '/../../lib/IDS/Config/Config.ini.php';
        $this->init = Init::init($this->path);
    }

	function testCachingNone() {
    	$this->init->config['Caching']['caching'] = 'none';
    	$this->assertFalse(CacheFactory::factory($this->init, 'storage'));
    }

    function testCachingFile() {
        $this->init->config['Caching']['caching'] = 'file';
        $this->init->config['Caching']['expiration_time'] = 0;
        $this->assertTrue(CacheFactory::factory($this->init, 'storage') instanceof FileCache);
    }

    function testCachingFileSetCache() {
        $this->init->config['Caching']['caching'] = 'file';
        $this->init->config['Caching']['expiration_time'] = 0;
        $cache = CacheFactory::factory($this->init, 'storage');
        $cache = $cache->setCache(array(1,2,3,4));
        $this->assertTrue($cache instanceof FileCache);
    }

    function testCachingFileGetCache() {
        $this->init->config['Caching']['caching'] = 'file';
        $this->init->config['Caching']['path'] =  dirname(__FILE__) . '/../../lib/IDS/tmp/default_filter.cache';
        $this->init->config['Caching']['expiration_time'] = 0;
        $cache = CacheFactory::factory($this->init, 'storage');
        $cache = $cache->setCache(array(1,2,3,4));
        $this->assertEquals($cache->getCache(), array(1,2,3,4));
    }

    function testCachingSession() {
        $this->init->config['Caching']['caching'] = 'session';
        $this->assertTrue(CacheFactory::factory($this->init, 'storage') instanceof SessionCache);
    }

    function testCachingSessionSetCache() {
        $this->init->config['Caching']['caching'] = 'session';

        $cache = CacheFactory::factory($this->init, 'storage');
        $cache = $cache->setCache(array(1,2,3,4));
        $this->assertTrue($cache instanceof SessionCache);
    }

    function testCachingSessionGetCache() {
        $this->init->config['Caching']['caching'] = 'session';

        $cache = CacheFactory::factory($this->init, 'storage');
        $cache = $cache->setCache(array(1,2,3,4));
        $this->assertEquals($cache->getCache(), array(1,2,3,4));
    }

    function testCachingSessionGetCacheDestroyed() {
        $this->init->config['Caching']['caching'] = 'session';

        $cache = CacheFactory::factory($this->init, 'storage');
        $cache = $cache->setCache(array(1,2,3,4));
        $_SESSION['PHPIDS']['storage'] = null;
        $this->assertFalse($cache->getCache());
    }

    function tearDown() {
        @unlink(dirname(__FILE__) . '/../../lib/IDS/tmp/default_filter.cache');
        @unlink(dirname(__FILE__) . '/../../lib/IDS/tmp/memcache.timestamp');
        @unlink(dirname(__FILE__) . '/../../tmp/default_filter.cache');
        @unlink(dirname(__FILE__) . '/../../tmp/memcache.timestamp');
    }
}

/**
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: sw=4 ts=4 expandtab
 */
