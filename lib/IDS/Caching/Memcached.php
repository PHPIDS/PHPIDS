<?php

/**
 * PHPIDS
 * Requirements: PHP5, SimpleXML
 * @package     PHPIDS
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
 */

require_once 'IDS/Caching/Interface.php';

/**
 * File caching wrapper
 * 
 * This class inhabits functionality to get and set cache via memcached.
 * 
 * @author		.mario <mario.heiderich@gmail.com>
 *
 * @package		PHPIDS
 * @copyright   2007 The PHPIDS Group
 * @version		SVN: $Id:Memcached.php 517 2007-09-15 15:04:13Z mario $
 * @since       Version 0.4
 * @link        http://php-ids.org/
 */
class IDS_Caching_Memcached implements IDS_Caching_Interface {

    /**
     * Caching type
     *
     * @var string
     */
    private $type = NULL;     

    /**
     * Cache configuration
     *
     * @var array
     */
    private $config = NULL;    

    /**
     * Path to memcache timestamp file
     *
     * @var string
     */
    private $path = NULL;

    /**
     * Memcache object
     *
     * @var object
     */
    private $memcache = NULL;
    
    /**
     * Holds an instance of this class
     *
     * @var object
     */
    private static $cachingInstance = NULL; 
  

    /**
     * Constructor
     *
     * @param   string  $type   caching type
     * @param   array   $config caching configuration
     * @throws  Exception
     * @return  void
     */
    public function __construct($type, $config) {
        
        $this->type = $type;
        $this->config = $config;
        $this->memcache = $this->connect();       
        
        if (file_exists($this->path) && !is_writable($this->path)) {
            throw new Exception('Make sure all files in IDS/tmp are writeable!'); 
        }             
    }      
    
    /**
     * Returns an instance of this class
     * 
     * @param   string  $type   caching type
     * @param   array   $config caching configuration
     * @return  object  $this
     */
    public static function getInstance($type, $config) {
        
        if (!self::$cachingInstance) {
            self::$cachingInstance = new IDS_Caching_Memcached($type, $config);
        }

        return self::$cachingInstance;
    }
    
    /**
     * Writes cache data
     *
     * @param   array   $data
     * @throws  Exception
     * @return  object  $this
     */
    public function setCache(array $data) {
        
    	if (!file_exists($this->path)) {
            $handle = fopen($this->path , 'w');
            fclose($handle);       	   	
    	}

        if (!is_writable($this->path)) {
            throw new Exception('Make sure all files in IDS/tmp are writeable!'); 
        }    	
    	
    	if ((time()-filectime($this->path)) > $this->config['expiration_time']) {
            $this->memcache->set(
                $this->config['key_prefix'] . '.storage',
                $data
            );
        }

    	return $this;
    }
    
    /**
     * Returns the cached data
     *
     * Note that this method returns false if either type or file cache is not set
     *
     * @return  mixed   cache data or false
     */
    public function getCache() {

        // make sure filters are parsed again if cache expired
        if ((time()-filectime($this->path)) < $this->config['expiration_time']) {
            $data = $this->memcache->get($this->config['key_prefix'].'.storage');
            return $data;
        }

        return false;    	
    }
    
    /**
     * Connect to the memcached server
     *
     * @throws   Exception
     * @return  void
     */
    private function connect() {
        
    	if ($this->config['host'] && $this->config['port']) {
    	    // establish the memcache connection
            $this->memcache = new Memcache;
            $this->memcache->pconnect($this->config['host'], $this->config['port']); 
            $this->path = dirname(__FILE__).'/../../' . $this->config['tmp_path']; 
    	} else {
    		throw new Exception('Insufficient connection parameters');
    	}	    	
    }
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */
