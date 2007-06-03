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
* File wrapper
*
* This class is designed to store incoming data in
* a file and implements the singleton pattern
*
* @author	christ1an <ch0012@gmail.com>
*/
class IDS_Log_File implements IDS_Log_Interface {

	private $logfile = null;
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
				 Log_File::prepareData() is a string.'
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
