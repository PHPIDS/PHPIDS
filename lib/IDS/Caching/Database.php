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
 * Needed SQL:
 * 

    CREATE DATABASE IF NOT EXISTS `phpids` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
    DROP TABLE IF EXISTS `cache`;
	CREATE TABLE `cache` (
		`type` VARCHAR( 32 ) NOT NULL ,
		`data` TEXT NOT NULL ,
		`created` DATETIME NOT NULL ,
		`modified` DATETIME NOT NULL
	) ENGINE = MYISAM ;
 */

/**
 * IDS Database caching class
 * 
 * This class will be built with the caching factory and inhabits 
 * all logic to get and set the caching data via database
 * 
 * @author Mario Heiderich <mario.heiderich@gmail.com>   
 */
class IDS_Caching_Database implements IDS_Caching_Interface {

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
     * the db connection handle
     */
    private $handle = NULL;

    /**
     * Constructor
     *
     * @param string the caching type
     * @param array the caching configuration
     */
    public function __construct($type, $config) {
        
        $this->type = $type;
        $this->config = $config;
        $this->handle = $this->connect();       
    }    
    
    /**
     * the method to invoke the database caching as singleton
     * 
     * @param string the type like storage etc.
     * @return object the instance of the caching class
     */
    public static function getInstance($type, $config) {
        
        if (!self::$cachingInstance) {
            self::$cachingInstance = new IDS_Caching_Database($type, $config);
        }
        return self::$cachingInstance;
    }

    /**
     * The setter for the database cache
     *
     * @param array $data
     * @return object this instance
     * @throws PDOException
     */
    public function setCache(array $data) {

    	$handle = $this->handle;
    	
        foreach($handle->query('SELECT created FROM `'.mysql_escape_string($this->config['table']).'`') as $row) {
        	if((time()-strtotime($row['created'])) > $this->config['expiration_time']) {
		        
        		try {
		        	$handle->query('TRUNCATE '.mysql_escape_string($this->config['table']).'');
		            $statement = $handle->prepare('
		                INSERT INTO `'.mysql_escape_string($this->config['table']).'` (
		                    type,
		                    data,
		                    created,
		                    modified
		                ) 
		                VALUES (
		                    :type,
		                    :data,
		                    now(), 
		                    now()
		                )
		            ');                                            
		
		            $statement->bindParam('type', mysql_escape_string($this->type));
		            $statement->bindParam('data', serialize($data));
		            
		            if (!$statement->execute()) { 
		                throw new PDOException($statement->errorCode());     
		            }            
		            
		        } catch (PDOException $e) {
		            die('PDOException: ' . $e->getMessage());       
		        }               		
        	}
        }
    	
        return $this;
    }
    
    /**
     * The getter for the database caching - returns false if 
     * type or database cache is not set
     *
     * @return mixed database cache data or false
     * @throws PDOException
     */
    public function getCache() {

    	try{
	    	$handle = $this->handle;
	    	$result = $handle->prepare('SELECT * FROM '.mysql_escape_string($this->config['table'])
	    	   .' where type=?');
            $result->execute(array($this->type));	    	
	    	
	    	foreach($result as $row) {
	            return unserialize($row['data']);
	    	}
        } catch (PDOException $e) {
            die('PDOException: ' . $e->getMessage());       
        } 
    	   
        return false;
    }
    
    /**
     * this function tries to conect to the databse and 
     * returns the connection handle
     *
     * @return object the connection handle
     * @throws Exception
     */
    private function connect() {
        
    	// validate connection parameters
    	if (!$this->config['driver'] 
            || !$this->config['host'] 
                || !$this->config['port'] 
                    || !$this->config['database'] 
                        || !$this->config['user'] 
                            || !$this->config['password'] 
                                || !$this->config['table']) {
            
            throw new Exception('
                Insufficient connection parameters'
            ); 
        } 

        // try to connect
        try {
            $handle = new PDO(
                $this->config['driver'].':host='
                    .$this->config['host']
                        .';post='.$this->config['port']
                            .';dbname='.$this->config['database'], 
                $this->config['user'], 
                $this->config['password']
            );        
        } catch (PDOException $e) {
            die('PDOException: ' . $e->getMessage());       
        } 
        
        return $handle;
    }
}

/**
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */