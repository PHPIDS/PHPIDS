<?php

/**
 * PHP IDS
 *
 * Requirements: PHP5, SimpleXML, MultiByte Extension (optional)
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
* File wrapper
*
* This class is designed to store incoming data in
* a file and implements the singleton pattern
*
* @author	christ1an <ch0012@gmail.com>
*
* @version	$Id$
*/
class IDS_Log_File implements IDS_Log_Interface {

	private $logfile = NULL;
	private static $instances = array();

	/**
	* Constructor
	*
	* @param	string
	* @access	protected
	* @return	void
	*/
	protected function __construct($logfile) {
		$this->logfile = $logfile;
	}

	/**
	* Returns an instance of this class
	*
	* @param	string
	* @access	public
	* @return	object
	*/
	public static function getInstance($logfile) {
		if (!isset(self::$instances[$logfile])) {
			self::$instances[$logfile] = new IDS_Log_File($logfile);
		}

		return self::$instances[$logfile];
	}

	/**
	* Just for the sake of completeness
	* of a correct singleton pattern
	*/
	private function __clone() { }

	/**
	* Converts data that is passed to IDS_Log_File::execute()
	* into a format that can be stored in a file
	*
	* You might edit this method to your requirements
	*
	* @param	mixed
	* @access	protected
	* @return	mixed
	*/
	protected function prepareData($data) {
		return serialize($data);
	}

	/**
	* Stores incoming data record into a file
	*
	* @param	mixed
	* @access	public
	* @return	mixed	bool or exception object on failure
	*/
	public function execute(IDS_Report $data) {

		/**
		* In case the data has been modified before it might
		* be necessary to convert it to string since we can't
		* store array or object in a file
		*/
		$data = $this->prepareData($data);

		if (is_string($data)) {
			if (file_exists($this->logfile)) {
				$data = trim($data);

				if (!empty($data)) {
					$handle = fopen($this->logfile, 'a');
					fwrite($handle, $data . "\n");
					fclose($handle);
				}
			} else {
				throw new Exception(
					'Given file does not exist.'
				);
			}
		} else {
			throw new Exception(
				'Please make sure that data returned by
				IDS_Log_File::prepareData() is a string.'
			);
		}

		return true;
	}

}
/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */
