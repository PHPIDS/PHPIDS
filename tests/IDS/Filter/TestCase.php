<?php
require_once 'PHPUnit2/Framework/TestCase.php';

require_once dirname(__FILE__) . '/../../../phpids/filter.php';
class IDS_Filter_TestCase extends PHPUnit2_Framework_TestCase
{
	public function testObjectConstruction()
	{
		#$filter = new IDS_Filter_Regexp('^test$', 'My description', array('foo', 'bar'), 12);
		$filter = new IDS_Filter_Regexp('^test$', array('foo', 'bar'), 12);

		$this->assertTrue($filter->match('test'));
		#$this->assertEquals("My description", $filter->getDescription(), "Should return description");
		$this->assertEquals(array("foo", "bar"), $filter->getTags(), "Should return array/list of tags");
		$this->assertEquals('^test$', $filter->getRule());
		$this->assertEquals(12, $filter->getImpact());
	}

	public function testExceptions()
	{
	#	$filter = new IDS_Filter_Regexp('^test$', 'My description', array('foo', 'bar'), 10);
		$filter = new IDS_Filter_Regexp('^test$', array('foo', 'bar'), 10);

		try {
			$filter->match(1);
			$this->fail("Expected InvalidArgumentException");
		} catch (InvalidArgumentException $e) {}


		try {
			#$filter = new IDS_Filter_Regexp('^test$', 'my desc', array('foo'), 'test');
			$filter = new IDS_Filter_Regexp('^test$', array('foo'), 'test');

			$this->fail("Expected InvalidArgumentException");
		} catch (InvalidArgumentException $e) {}

		try {
			#$filter = new IDS_Filter_Regexp(1, 'my desc', array("foo"), 'bla');
			$filter = new IDS_Filter_Regexp(1, array("foo"), 'bla');

			$this->fail("Excpected InvalidArgumentException");
		} catch (InvalidArgumentException $e) {}
	}
}
