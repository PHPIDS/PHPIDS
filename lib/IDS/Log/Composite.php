<?php

/**
 * PHPIDS
 * Requirements: PHP5, SimpleXML
 *
 * Copyright (c) 2007 PHPIDS group (http://php-ids.org)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 2 of the license.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @package	PHPIDS
 */

require_once 'IDS/Log/Interface.php';

/**
 * Log Composite
 *
 * This class implements the composite pattern to allow to work with multiple
 * logging wrappers at once.
 *
 * @author		christ1an <ch0012@gmail.com>
 *
 * @package		PHPIDS
 * @copyright   2007 The PHPIDS Group
 * @version		SVN: $Id:Composite.php 517 2007-09-15 15:04:13Z mario $
 * @link        http://php-ids.org/
 */
class IDS_Log_Composite {

    /**
     * Holds registered logging wrapper
     *
     * @var array
     */
	public $loggers = array();

    /**
     * Iterates through registered loggers and executes them
     *
     * @param   object  $data   IDS_Report object
     * @return  void
     */
	public function execute(IDS_Report $data) {
		foreach ($this->loggers as $logger) {
			$logger->execute($data);
		}
	}

    /**
     * Registers a new logging wrapper
     *
     * Only valid IDS_Log_Interface instances passed to this function will be registered
     *
     * @return  void
     */
	public function addLogger() {
		
		$args = func_get_args();

		foreach ($args as $class) {
            if (!in_array($class, $this->loggers) && ($class instanceof IDS_Log_Interface)) {
                $this->loggers[] = $class;
            }
        }
    }

	/**
	* Removes a logger
	*
	* @param	object  $logger IDS_Log_Interface object
	* @return   boolean
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
