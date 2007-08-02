<?php

/**
 * PHP IDS
 * 
 * Requirements: PHP5, SimpleXML
 *
 * Copyright (c) 2007 PHPIDS (http://php-ids.org)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 2 of the license.
 *
 * This program is distributed in the hope that it will be useful, 
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

/*
 * 	1st: require the needed files
 */
set_include_path(
	get_include_path()
	. PATH_SEPARATOR
	. '../../lib/'
);
require_once 'IDS/Monitor.php';
require_once 'IDS/Filter/Storage.php';

try {
    /*
    * 2nd: instanciate the storage object and fetch the rules
    */
    $storage = new IDS_Filter_Storage();
    $storage->getFilterFromXML('../../lib/IDS/default_filter.xml');
    
    /*
    * 3rd: instanciate the ids and start the detection
    * 
    * here we are using $_GET but you can pass any 
    * array you want like $_REQUEST, $_SESSION etc.
    */
    $get = new IDS_Monitor($_GET, $storage);
    $result = $get->run();
    
    /*
    * in the result object you will find any suspicious 
    * fields of the passed array enriched with additional info
	*
	* Note: it is moreover possible to dump this information by
	* simply echoing the result object, since IDS_Report implemented
	* a __toString method whose output is identical with the one below.
	*
	* <code>
	*  echo $result;
	* </code>
    */
    if (!$result->isEmpty()) {
        
        # Get the overall impact
        echo "Total impact: {$result->getImpact()}<br/>\n";
        
        # Get array of every tag used
        echo "Affected tags: " . join(", ", $result->getTags()) . "<br/>\n\n";
        
        # Iterate through the result an get every event (IDS_Event)
        foreach ($result as $event) {
            echo "<br />\nVariable: " . htmlspecialchars($event->getName()) . " | Value: " . htmlspecialchars($event->getValue()) . "<br/>\n";
            echo "Impact: {$event->getImpact()} | Tags: " . join(", ", $event->getTags()) . "<br/>\n";
            
            # Iterator throught every filter 
            foreach ($event as $filter) {
                echo "Description: {$filter->getDescription()} | ";
                echo "Tags: " . join(", ", $filter->getTags()) . "<br/>\n";
            }
        }
    }
	
    /*
    * now store the data using IDS_Log_Composite and
    * Log_File
    */
    require_once '../../lib/IDS/Log/File.php';
    require_once '../../lib/IDS/Log/Composite.php';
   
    $compositeLog = new IDS_Log_Composite();
    $compositeLog->addLogger(
       IDS_Log_File::getInstance('log.txt')
	);

    
    if (!$result->isEmpty()) {
        $compositeLog->execute($result);
    }
    
} catch (Exception $e) {
    /*
    * sth went terribly wrong - maybe the 
    * filter rules weren't found?
    */
    printf(
    'An error occured: %s',
    $e->getMessage()
    );
}
