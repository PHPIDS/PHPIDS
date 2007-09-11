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
 * IDS File caching class
 * 
 * This class will be built with the caching factory and inhabits 
 * all logic to get and set the caching data via static file
 * 
 * @author Mario Heiderich <mario.heiderich@gmail.com>   
 */
class IDS_Caching_File implements IDS_Caching_Interface {

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
     * the path to the cache file
     */
    private $path = NULL;

    /**
     * Constructor
     *
     * @param string the caching type
     * @param array the caching configuration
     */
    public function __construct($type, $config) {
        
        $this->type = $type;
        $this->config = $config;
        $this->path = dirname(__FILE__).'/../../' . $this->config['path']; 
        
        if(file_exists($this->path) && !is_writable($this->path)) {
            throw new Exception('Make sure all files in IDS/tmp are writeable!'); 
        }             
    }    
    
    /**
     * the method to invoke the file caching as singleton
     * 
     * @param string the type like storage etc.
     * @return object the instance of the caching class
     */
    public static function getInstance($type, $config) {
        
        if (!self::$cachingInstance) {
            self::$cachingInstance = new IDS_Caching_File($type, $config);
        }
        return self::$cachingInstance;
    }
    
    /**
     * The setter for the file cache
     *
     * @param array $data
     * @return object this instance
     */
    public function setCache(array $data) {

        if( (!file_exists($this->path) || (time()-filectime($this->path)) > $this->config['expiration_time'])) {
        	$handle = fopen($this->path , 'w');
	        fwrite($handle, serialize($data));
	        fclose($handle);        	
        }
        
        return $this;
    }
    
    /**
     * The getter for the file caching - returns false if 
     * type or file cache is not set
     *
     * @return mixed file cache data or false
     */
    public function getCache() {
        
    	// make sure filters are parsed again when expired
    	if(file_exists($this->path) && (time()-filectime($this->path)) < $this->config['expiration_time']) {
            $data = unserialize(file_get_contents($this->path));
	  	    return $data;
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