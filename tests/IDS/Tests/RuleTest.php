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
namespace IDS\Tests;

use IDS\Init;
use IDS\Monitor;

class RuleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Init
     */
    protected $init;

    public function getPayloads()
    {
        return array(
            array(20, "if  ("),
            array(20, "if ("),
            array(20, "if("),
            array(20, "elseif  ("),
            array(20, "elseif ("),
            array(20, "elseif("),
            array(20, "for  ("),
            array(20, "for ("),
            array(20, "for("),
            array(20, "foreach  ("),
            array(20, "foreach ("),
            array(20, "foreach("),
            array(20, "for each  ("),
        );
    }

    public function setUp()
    {
        $this->init = Init::init(IDS_CONFIG);
        $this->init->config['General']['tmp_path'] = IDS_TEMP_DIR;
        $this->init->config['Caching']['path'] = IDS_FILTER_CACHE_FILE;
        $this->init->config['General']['filter_type'] = IDS_FILTER_TYPE;
        $this->init->config['General']['filter_path'] = IDS_FILTER_SET;
    }

    /** @dataProvider getPayloads */
    public function testSingleRules($ruleId, $payload)
    {
        $monitor = new Monitor($this->init);
        $result = $monitor->run(array('payload' => $payload));

        $event = $result->getEvent('payload');
        $this->assertInstanceOf('IDS\Event', $event);

        $filters = $event->getFilters();
        $this->assertCount(1, $filters);

        $this->assertEquals($ruleId, $filters[0]->getId());
    }
}
