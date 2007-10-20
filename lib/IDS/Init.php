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

/**
 * Framework initiation
 * 
 * This class is used for the purpose to initiate the framework and inhabits
 * functionality to parse the needed configuration file.
 *
 * @author		.mario <mario.heiderich@gmail.com>
 * @author		christ1an <ch0012@gmail.com>
 *
 * @package		PHPIDS
 * @copyright   2007 The PHPIDS Group
 * @version		SVN: $Id:Init.php 517 2007-09-15 15:04:13Z mario $
 * @since       Version 0.4
 * @link        http://php-ids.org/
 */
class IDS_Init {
	
    /**
     * Holds config settings
     *
     * @var array
     */
	public $config = NULL;
	
	/**
     * Instance of this class depending on the supplied config file
     *
     * @var array
     * @static
     */
	private static $instances = array();
	
	/**
	 * Path to the config file
     *
     * @var string
     */
	private $configPath = NULL;

	/**
     * Constructor
     *
     * Includes needed classes and parses the configuration file
     *
     * @return  object  $this
     * @throws  Exception
     */
	private function __construct($configPath) {
		
        require_once 'IDS/Monitor.php';
        require_once 'IDS/Filter/Storage.php';
		
		$this->setConfigPath($configPath);
        $this->config = parse_ini_file($this->configPath, true);
		
        return $this;
	}
	
    /**
     * Permitting to clone this object
     *
     * For the sake of correctness of a singleton pattern, this is necessary
	 */
	public final function __clone() {}

	/**
     * Returns an instance of this class
     *
     * @param   string  $configPath
     * @return  object
     */
	public static function init($configPath) {
        if (!isset(self::$instances[$configPath])) {
        	self::$instances[$configPath] = new IDS_Init($configPath);
        }
		
		return self::$instances[$configPath];
    }

    /**
     * Sets the path to the configuration file
     *
     * @param   string  $path
     * @throws  Exception
     * @return  void
     */
    public function setConfigPath($path) {
        if (file_exists($path)) {
            $this->configPath = $path;
        } else {
            throw new Exception(
            	'Configuration file could not be found'
            );
        }
    }

    /**
     * Returns path to configuration file
     *
     * @return  string
     */
    public function getConfigPath() {
    	return $this->configPath;
    }
    
    /**
     * Merges new settings into the exsiting ones or overwrites them
     * 
     * @param	array	$config
     * @param	boolean	$overwrite
     * @return	void
     */
    public function setConfig(Array $config, $overwrite = false) {
    	
    	if($overwrite) {
    	   $this->config = array_merge($this->config, $config);
    	} else {
    	   $this->config = array_merge($config, $this->config);	
    	}
    }
    
    /**
     * Returns the config array
     * 
     * @return array the config array
     */
    public function getConfig() {
    	
    	return $this->config;
    }
    	
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */
