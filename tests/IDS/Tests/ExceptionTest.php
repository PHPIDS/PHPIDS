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
namespace IDS\Tests;

use IDS\Report;
use IDS\Event;
use IDS\Filter;
use IDS\Init;
use IDS\Monitor;

class ExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Report
     */
    protected $report;

    /**
     * @var Init
     */
    protected $init;

    public function setUp()
    {
        $this->report = new Report(array(
            new Event("key_a", 'val_b',
                array(
                    new Filter(1, '^test_a1$', 'desc_a1', array('tag_a1', 'tag_a2'), 1),
                    new Filter(1, '^test_a2$', 'desc_a2', array('tag_a2', 'tag_a3'), 2)
                )
            ),
            new Event('key_b', 'val_b',
                array(
                    new Filter(1, '^test_b1$', 'desc_b1', array('tag_b1', 'tag_b2'), 3),
                    new Filter(1, '^test_b2$', 'desc_b2', array('tag_b2', 'tag_b3'), 4),
                )
            )
        ));

        $this->init = Init::init(IDS_CONFIG);
    }

    public function testEventConstructorExceptions1()
    {
        $this->setExpectedException('InvalidArgumentException');
        new Event(array(1,2), 'val_b',
                array(
                    new Filter(1, '^test_a1$', 'desc_a1', array('tag_a1', 'tag_a2'), 1),
                    new Filter(1, '^test_a2$', 'desc_a2', array('tag_a2', 'tag_a3'), 2)
                )
        );
    }

    public function testEventConstructorExceptions2()
    {
        $this->setExpectedException('InvalidArgumentException');
        new Event("key_a", array(1,2),
                array(
                    new Filter(1, '^test_a1$', 'desc_a1', array('tag_a1', 'tag_a2'), 1),
                    new Filter(1, '^test_a2$', 'desc_a2', array('tag_a2', 'tag_a3'), 2)
                )
        );
    }

    public function testEventConstructorExceptions3()
    {
        $this->setExpectedException('InvalidArgumentException');
        new Event("key_a", 'val_b', array(1,2));
    }

    public function testGetEventException()
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->report->getEvent(array(1,2,3));
    }

    public function testHasEventException()
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->report->hasEvent(array(1,2,3));
    }

    public function testInitConfigWrongPathException()
    {
        $this->setExpectedException('InvalidArgumentException');
        Init::init('IDS/Config/Config.ini.wrong');
    }

    public function testWrongXmlFilterPathException()
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->init->config['General']['filter_type'] = 'xml';
        $this->init->config['General']['filter_path'] = 'IDS/wrong_path';
        new Monitor($this->init);
    }
}
