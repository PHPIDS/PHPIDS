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
* Email wrapper
*
* This class is designed to send incoming data 
* via email and implements the singleton pattern
*
* @author	christ1an <ch0012@gmail.com>
*/
class IDS_Log_Email implements IDS_Log_Interface {

	private $address 			= null;
	private $subject			= null;
	private $additionalHeaders 	= null;
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
	* Converts data that is passed to Log_File::execute()
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
				 Log_File::prepareData() is a string.'
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

