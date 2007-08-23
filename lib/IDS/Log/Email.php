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

require_once 'IDS/Log/Interface.php';

/**
* Email wrapper
*
* This class is designed to send incoming data
* via email and implements the singleton pattern
*
* @author	christ1an <ch0012@gmail.com>
* @version	$Id$
*/
class IDS_Log_Email implements IDS_Log_Interface {

	private $address 			= NULL;
	private $subject			= NULL;
	private $additionalHeaders 	= NULL;
	private static $instances	= array();
	
	public	$safeMode = array(
		'mode'			=> 'on',
		'allowedRate'	=> 15,
		'protocolDir'	=> 'IDS/tmp/',
		'filePrefix'	=> 'IDS_Log_Email_'
	);
	
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
	public static function getInstance($address, $subject, $headers = NULL) {
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
	* Detects spam attempts
	*
	* To avoid mail spam through this logging class this function
	* is used to detect such attempts based on the alert
	* frequency
	*
	* @return	boolean
	*/
	protected function isSpamAttempt() {
		
		/**
		* loop through all files in the tmp directory and
		* delete garbage files
		*/
		$dir = $this->safeMode['protocolDir'];
		$numPrefixChars = strlen($this->safeMode['filePrefix']);
		
		$files = scandir($dir);
		foreach ($files as $file) {
			if (is_file($dir . $file)) {
				if (substr($file, 0, $numPrefixChars) == $this->safeMode['filePrefix']) {
					$lastModified = filemtime($dir . $file);
					
					if ((time() - $lastModified) > 3600) {
						unlink($dir . $file);
					}
				}
			}
		}
		/**
		* end deleting garbage files
		*/
		
		$remoteAddr = $_SERVER['REMOTE_ADDR'];
		$userAgent	= $_SERVER['HTTP_USER_AGENT'];
		
		$filename	= $this->safeMode['filePrefix'] . md5($remoteAddr . $userAgent) . '.tmp';
		$file		= $dir . $filename;
		
		if (!file_exists($file)) {
			$handle = fopen($file, 'w');
			fwrite($handle, time());
			fclose($handle);
			
			return false;
		}
		
		$lastAttack = file_get_contents($file);
		$difference = time() - $lastAttack;
		if ($difference > $this->safeMode['allowedRate']) {
			unlink($file);
		} else {
			return true;
		}

		return false;
	}

	/**
	* Converts data that is passed to IDS_Log_Email::execute()
	* into a format that can be stored in a file
	*
	* You might edit this method to your requirements
	*
	* @param	mixed
	* @access	protected
	* @return	string
	*/
	protected function prepareData($data) {
		
		$format	 = "The following attack has been detected by PHPIDS\n\n";
		$format .= "IP: %s \n";
		$format .= "Date: %s \n";
		$format .= "Impact: %d \n";
		$format .= "Affected tags: %s \n";
		
		$attackedParameters = '';
		foreach ($data as $event) {
			$attackedParameters .= $event->getName() . '=' . urlencode($event->getValue()) . ", ";
		}
		
		$format .= "Affected parameters: %s \n";
		$format .= "Request URI: %s";
		
		return sprintf(
			$format,
			$_SERVER['REMOTE_ADDR'],
			date('c'),
			$data->getImpact(),
			join(' ', $data->getTags()),
			trim($attackedParameters),
			urlencode($_SERVER['REQUEST_URI'])
		);
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
	
		if ($this->safeMode['mode'] == 'on') {
			if ($this->isSpamAttempt()) {
				return false;
			}
		}

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

			if (!empty($this->address)) {
				if (is_array($this->address)){
					foreach ($this->address as $address) {
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
