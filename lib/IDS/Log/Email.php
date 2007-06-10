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
* Email wrapper
*
* This class is designed to send incoming data
* via email and implements the singleton pattern
*
* @author	christ1an <ch0012@gmail.com>
*/
class IDS_Log_Email implements IDS_Log_Interface {

	private $address 			= NULL;
	private $subject			= NULL;
	private $additionalHeaders 	= NULL;
	private static $instances	= array();

	/**
	* Constructor
	*
	* @param	string
	* @access	protected
	* @return	void
	*/
	protected function __construct($address, $subject, $headers) {
		$this->address = $address;
		$this->subject = $subject;
		$this->additionalHeaders = $headers;
	}

	/**
	* Returns an instance of this class
	*
	* @param	string
	* @access	public
	* @return	object
	*/
	public static function getInstance($address, $subject, $headers = null) {
		if (!isset(self::$instances[$address])) {
			self::$instances[$address] = new IDS_Log_Email(
				$address,
				$subject,
				$headers
			);
		}

		return self::$instances[$address];
	}

	/**
	* Just for the sake of completeness
	* of a correct singleton pattern
	*/
	private function __clone() { }

	/**
	* Converts data that is passed to IDS_Log_Mail::execute()
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
	* Sends incoming data via email to given
	* address
	*
	* @param	mixed
	* @access	public
	* @return	mixed	bool or exception object on failure
	*/
	public function execute(IDS_Report $data) {

		/**
		* In case the data has been modified before it might
		* be necessary to convert it to string since it's pretty
		* senseless to send array or object via e-mail
		*/
		$data = $this->prepareData($data);

		if (is_string($data)) {
			$data = trim($data);

			// if headers are passed as array, we need to make
			// a string of it
			if (is_array($this->additionalHeaders)) {
				$headers = "";
				foreach ($this->additionalHeaders as $header) {
					$headers .= $header . "\r\n";
				}
			} else {
				$headers = $this->additionalHeaders;
			}

			if(!empty($this->address)){
				if(is_array($this->address)){
					foreach($this->address as $address){
						$this->send($address, $data, $headers);
					}
				} else {
					$this->send($this->address, $data, $headers);
				}
			}

		} else {
			throw new Exception(
				'Please make sure that data returned by
				 IDS_Log_Email::prepareData() is a string.'
			);
		}

		return true;
	}

	/**
	 * This function sends out the reporting mail with the given
	 * address, headers and body
	 *
	 * @param string recipient's address
	 * @param string mailbody
	 * @param string mailheader
	 * @return boolean
	 */
	protected function send($address, $data, $headers){
		return mail(
			$address,
			$this->subject,
			$data,
			$headers
		);
	}

}
/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */

