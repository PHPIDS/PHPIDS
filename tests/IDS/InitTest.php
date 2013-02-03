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
 * @package    PHPIDS tests
 */

namespace IDS;

class InitTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->path = dirname(__FILE__) . '/../../lib/IDS/Config/Config.ini.php';
        $this->init = Init::init($this->path);
    }

    public function testInit()
    {
        $this->assertTrue($this->init instanceof Init);
    }

    public function testInitConfig()
    {
        $keys = array('General', 'Logging', 'Caching');
        $this->assertEquals($keys, array_keys($this->init->config));
    }

    public function testInitClone()
    {
        $config2 = clone $this->init;
        $this->assertEquals($config2, $this->init);
    }

    public function testInitGetConfigPath()
    {
        $this->assertEquals($this->init->getConfigPath(), $this->path);
    }

    public function testInitSetConfigOverwrite()
    {
        $this->init->setConfig(array('General' => array('filter_type' => 'json')), true);
        $this->assertEquals($this->init->config['General']['filter_type'], 'json');

        $this->init->setConfig(
            array('General' => array('exceptions' => array('foo'))),
            true
        );
        $this->assertSame(
            array('foo', 'GET.__utmc'),
            $this->init->config['General']['exceptions']
        );
    }

    public function testInitSetConfigNoOverwrite()
    {
        $this->init->setConfig(array('General' => array('filter_type' => 'xml')), true);
        $this->init->setConfig(array('General' => array('filter_type' => 'json')));
        $this->assertEquals($this->init->config['General']['filter_type'], 'xml');
    }

    public function testInitGetConfig()
    {
        $data = $this->init->getConfig();
        $this->assertEquals($this->init->config, $data);
    }

    public function testInstanciatingInitObjectWithoutPassingConfigFile()
    {
        $init = Init::init();
        $this->assertInstanceOf('\\IDS\\Init', $init);
        $this->assertSame($init, Init::init());
    }
}

/**
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: sw=4 ts=4 expandtab
 */
