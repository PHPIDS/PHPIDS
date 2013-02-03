<?php
namespace IDS\Tests;

use IDS\Init;
use IDS\Filter;
use IDS\Filter\Storage;

class FilterSetTest extends \PHPUnit_Framework_TestCase
{
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
