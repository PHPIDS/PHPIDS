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
use IDS\Filter;
use IDS\Filter\Storage;

class FilterSetTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    protected $jsonFilter;

    /**
     * @var array
     */
    protected $xmlFilter;

    public function setUp()
    {
        $this->jsonFilter = $this->getFilterset('json');
        $this->xmlFilter = $this->getFilterSet('xml');
    }

    private function getFilterSet($type)
    {
        $init = Init::init(IDS_CONFIG);
        $init->config['General']['filter_type'] = strtolower($type);
        $init->config['General']['filter_path'] = constant('IDS_FILTER_SET_' . strtoupper($type));
        $init->config['Caching']['caching'] = 'none';
        $storage = new Storage($init);

        return $storage->getFilterSet();
    }

    public function testRegularExpressionsMatch()
    {
#        $this->assertSame(count($this->jsonFilter), count($this->xmlFilter));

        foreach ($this->jsonFilter as $pos => $filter) {
            $this->assertFiltersMatch($filter, $this->xmlFilter[$pos]);
        }

        foreach ($this->xmlFilter as $pos => $filter) {
            $this->assertFiltersMatch($filter, $this->jsonFilter[$pos]);
        }
    }

    protected function assertFiltersMatch(Filter $left, Filter $right)
    {
        $this->assertNotSame($left, $right);
        $this->assertFilterMethodMatch($left, $right, 'getId');
        $this->assertFilterMethodMatch($left, $right, 'getTags');
        $this->assertFilterMethodMatch($left, $right, 'getDescription');
        $this->assertFilterMethodMatch($left, $right, 'getImpact');
        $this->assertFilterMethodMatch($left, $right, 'getRule');
    }

    protected function assertFilterMethodMatch(Filter $left, Filter $right, $method)
    {
        $this->assertNotSame($left, $right);
        $this->assertSame(
            $left->{$method}(),
            $right->{$method}(),
            sprintf('Result of "%s" does not match for filter %d/%d', $method, $left->getId(), $right->getId())
        );
    }
}
