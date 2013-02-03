<?php
namespace IDS\Tests;

use IDS\Init;
use IDS\Monitor;

class RuleTest extends \PHPUnit_Framework_TestCase
{
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
        $monitor = new Monitor(array('payload' => $payload), $this->init);
        $result = $monitor->run();

        $event = $result->getEvent('payload');
        $this->assertInstanceOf('IDS\Event', $event);

        $filters = $event->getFilters();
        $this->assertCount(1, $filters);

        $this->assertEquals($ruleId, $filters[0]->getId());
    }
}
