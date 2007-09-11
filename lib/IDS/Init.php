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

require_once 'IDS/Monitor.php';
require_once 'IDS/Filter/Storage.php';

/**
 * This class inhabits functionality to parse the frameworks configuration
 * file and return it as a object.
 *
 * @author	.mario <mario.heiderich@gmail.com>
 *
 * @version	$Id$
 */
class IDS_Init {
	
	/**
	* Holds config settings
	*
	* @access	public
	* @var		array
	*/
	public $config = NULL;
	
	/**
	* Instance of this class
	*
	* @access	private
	* @var		object
	*/
	private static $configInstance = NULL;
	
	/**
	* Path to the config file
	*
	* @access	public
	* @var		string
	*/
	const configPath = 'IDS/Config/Config.ini';

	/**
	* Constructor
	* 
	* This method parses the ini file and checks if the tmp folder is 
	* writeable - else an exception is thrown
	*
	* @access	private
	* @return	object
	*/
	private function __construct() {
		
        $this->config = parse_ini_file(self::configPath, true);
	    if(!is_writeable(dirname(__FILE__) . '/../' .$this->config['IDS_Basic']['tmp_path'])) {
            
	    	throw new Exception('Please make sure the IDS/tmp folder is writable');                
        }        
        
        return $this;
	}
	
	/**
	 * This method stub makes sure the object can't be cloned
	 *
	 */
	public final function __clone() {
		throw new Exception('Object cannot be cloned');
	}

	/**
	* Returns an instance of this class
	*
	* @access	public
	* @return	object
	*/
	public static function init() {
        if (!self::$configInstance) {
        	self::$configInstance = new IDS_Init;
        }
		
		return self::$configInstance;
	}
}

/**
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */