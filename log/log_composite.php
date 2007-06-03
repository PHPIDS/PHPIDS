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

require_once('log_interface.php');

/**
* Log composite class
*
* Class that implements the composite pattern to provide multiple
* logging mechanism at once
*
* @author 	christ1an <ch0012@gmail.com>
*/
class IDS_Log_Composite implements IDS_Log_Interface {
	
	/**
	* Holds stored loggers
	* 
	* @var		array
	* @access	public
	*/
	public $loggers = array();
	
	/**
	* Loops through registered loggers and executes
	* their execute method
	*
	* @param	mixed
	* @access	public
	* @return	void
	*/
	public function execute(IDS_Report $data) {
		foreach ($this->loggers as $logger) {
			$logger->execute($data);
		}		
	}
	
	/**
	* Adds a logger
	*
	* Note that each logger must implement the IDS_Log_Interface
	* interface in order to assure the systems API
	*
	* @param	object
	* @access	public
	* @return	void
	*/
	public function addLogger(IDS_Log_Interface $logger) {
		if (!in_array($logger, $this->loggers)) {
			$this->loggers[] = $logger;
		}
	}
	
	/**
	* Removes a logger
	*
	* @param	object
	* @access	public	
	* @return	boolean
	*/
	public function removeLogger(IDS_Log_Interface $logger) {
		$key = array_search($logger, $this->loggers);
		
		if (isset($this->loggers[$key])) {
			unset($this->loggers[$key]);
			return true;
		}
		
		return false;
	}	
}
/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */

