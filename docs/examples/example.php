<?php

/**
 * PHPIDS
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
 * set the include path properly for PHPIDS
 */
set_include_path(
	get_include_path()
	. PATH_SEPARATOR
	. '../../lib/'
);
	
if (!session_id()) {
	session_start();
}
            
require_once 'IDS/Init.php';

try {
	
    # It's pretty easy to get the PHPIDS running
    # 1. Define what to scan
    $request = array_merge($_GET, $_POST);
	$init = IDS_Init::init();	
    
    # 2. Init the PHPIDS and fetch the results
    $ids = new IDS_Monitor($init, $request);
    $result = $ids->run();            

    /*
    * That's it - now you can analyze the results: 
    * 
    * In the result object you will find any suspicious 
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
        echo $result;
    }
	
    /*
    * All the following stuff is optional and takes care of 
    * implementing the PHPIDS logger
    * 
    */
    require_once '../../lib/IDS/Log/File.php';
    require_once '../../lib/IDS/Log/Composite.php';
   
    $compositeLog = new IDS_Log_Composite();
    $compositeLog->addLogger(
		IDS_Log_File::getInstance(
		  $init->config['IDS_Logging']['path']
		)  
	);
   
	/**
	* Note that you might also use different logging facilities
	* such as IDS_Log_Email or IDS_Log_Database
	*/
	require_once '../../lib/IDS/Log/Email.php';
    require_once '../../lib/IDS/Log/Database.php';

    $compositeLog->addLogger(
        IDS_Log_Email::getInstance(
            $init->config['IDS_Logging']['recipient'], 
            $init->config['IDS_Logging']['subject'],
            NULL, //optional headers
            $init->config['IDS_Logging']['safemode'],
            $init->config['IDS_Logging']['allowed_rate'], 
            $init->config['IDS_Basic']['tmp_path']
        ),
		IDS_Log_Database::getInstance(
            $init->config['IDS_Logging']['wrapper'], 
            $init->config['IDS_Logging']['user'], 
            $init->config['IDS_Logging']['password'], 
            $init->config['IDS_Logging']['table']
        )
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
