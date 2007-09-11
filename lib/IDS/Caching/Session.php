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

/**
 * IDS Session caching class
 * 
 * This class will be built with the caching factory and inhabits 
 * all logic to get and set the caching data via session
 * 
 * @author Mario Heiderich <mario.heiderich@gmail.com>   
 */
class IDS_Caching_Session implements IDS_Caching_Interface {

    /**
     * the caching instance
     */
    private static $cachingInstance = NULL;	

    /**
     * the caching type (storage etc.)
     */
    private $type = NULL;     
    
    /**
     * the PHPIDS config
     */
    private $config = NULL;

    /**
     * Constructor
     *
     * @param string the caching type
     * @param array the caching configuration
     */
    public function __construct($type, $config) {
        
        $this->type = $type;
        $this->config = $config;
    }    
    
    /**
     * the method to invoke the session caching as singleton
     * 
     * @param string the type like storage etc.
     * @return object the instance of the caching class
     */
    public static function getInstance($type, $config) {
        
    	if (!self::$cachingInstance) {
            self::$cachingInstance = new IDS_Caching_Session($type, $config);
    	}
        return self::$cachingInstance;
    }
    
    /**
     * The setter for the session cache
     *
     * @param array $data
     * @return object this instance
     */
    public function setCache(array $data) {
    
    	$_SESSION['PHPIDS'][$this->type] = $data;
    	return $this;
    }
    
    /**
     * The getter for the session caching - returns false if 
     * type or session cache is not set
     *
     * @return mixed session cache data or false
     */
    public function getCache() {
    	
    	if($this->type && $_SESSION['PHPIDS'][$this->type]) {
    		return $_SESSION['PHPIDS'][$this->type];
    	}
    	return false;
    }
}

/**
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */