<?php
require_once 'PHPUnit2/Framework/TestCase.php';
require_once dirname(__FILE__) . '/../../../phpids/event.php';
require_once dirname(__FILE__) . '/../../../phpids/filter.php';
class IDS_Event_TestCase extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->event = new IDS_Event("handled_key", "my val",
			array(
				new IDS_Filter_Regexp('^test$', 'my description', array('tag1', 'tag2'), 10),
				new IDS_Filter_Regexp('^test2$', 'my other desc', array('tag2', 'tag3'), 4)
			)
		);
	}

	public function testName()
	{
		$this->assertEquals('handled_key', $this->event->getName());
		$this->assertEquals("my val", $this->event->getValue());
	}

	public function testValueAggregation()
	{
		$this->assertEquals(14, $this->event->getImpact());
		$this->assertEquals(array('tag1', 'tag2', 'tag3'), $this->event->getTags());
	}

	public function testIterator()
	{
		$regexps = array('^test$', '^test2$');
		foreach ($this->event as $key => $filter)
			$this->assertEquals($regexps[$key], $filter->getRule());
		foreach ($this->event->getFilters() as $key => $filter)
			$this->assertEquals($regexps[$key], $filter->getRule());
	}

	public function testCount()
	{
		$this->assertEquals(2, count($this->event));
	}

	public function testCopy()
	{
		$filters = $this->event->getFilters();
		$filter[] = "foo";
		$this->assertEquals(2, count($this->event));
	}

	public function testIteratorAggregate()
	{
		$this->assertType('IteratorAggregate', $this->event);
		$this->assertType('IteratorAggregate', $this->event->getIterator());
	}
}
