<?php
/**
 * PHP IDS
 * 
 * Requirements: PHP5, SimpleXML, MultiByte Extension (optional)
 *  
 * Copyright 2007 Mario Heiderich for Ormigo 
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy 
 * of this software and associated documentation files (the "Software"), to deal 
 * in the Software without restriction, including without limitation the rights to use, 
 * copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the 
 * Software, and to permit persons to whom the Software is furnished to do so, 
 * subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in 
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR 
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, 
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL 
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, 
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN 
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

/**
 * This file is hosted on Google Code and can be 
 * discussed on Google Groups
 * 
 * http://code.google.com/p/phpids/ 
 * http://groups.google.de/group/php-ids/
 * 
 */


/**
 * 	1st: require the needed files
 */
require_once '../../phpids/ids.php';
require_once '../../phpids/storage.php';

try {
	/**
	 * 	2nd: instanciate the storage object and fetch the rules
	 */
	$storage = new IDS_Filter_Storage();
	$storage->getFilterFromXML('../../phpids/default_filter.xml');
	
	/**
	 * 	3rd: instanciate the ids and start the detection
	 * 
	 * 	here we are using $_GET but you can pass any 
	 * 	array you want like $_REQUEST, $_SESSION etc.
	 */
	$get = new IDS_Monitor($_GET, $storage);
	$report = $get->run();

	/**
	 * 	in the report object you will find any suspicious 
	 * 	fields of the passed array
	 */

	if (!$report->isEmpty()) {
		/** Get the overall impact */
		echo "Impact: {$report->getImpact()}\n";

		/** Get array of every tag used */
		echo "Tags: " . join(", ", $report->getTags()) . "\n";

		/** Iterate through the report an get every event (IDS_Event) */
		foreach ($report as $event) {
			echo "Variable: {$event->getName()} | Value: {$event->getValue()}\n";
			echo "Impact: {$event->getImpact()} | Tags: " . join(", ", $event->getTags()) . "\n";

			/** Iterator throught every filter */
			foreach ($event as $filter) {
				echo "Description: {$filter->getDescription()}\n";
				echo "Tags: " . join(", ", $filter->getTags()) . "\n";
			}
		}
	}

	/**
	* We store the data using IDS_Log_Composite and
	* Log_File
	*/
	require_once('../../log/log_file.php');
	require_once('../../log/log_composite.php');

	$compositeLog = new IDS_Log_Composite();
	$compositeLog->addLogger(
		IDS_Log_File::getInstance('test.txt')
	);
	
	if (!empty($result)) {
		$compositeLog->execute($result);
	}

} catch (Exception $e) {
	/**
	 * 	sth went terribly wrong - maybe the 
	 * 	filter rules weren't found?
	 */
	printf(
		'An error occured: %s',
		$e->getMessage()
	);
}