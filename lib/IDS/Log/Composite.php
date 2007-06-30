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

require_once 'IDS/Log/Interface.php';

/**
* Log composite class
*
* Class that implements the composite pattern to provide multiple
* logging mechanism at once
*
* @author 	christ1an <ch0012@gmail.com>
*
* @version	$Id$
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