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

require_once 'IDS/Caching/Interface.php';

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
 * Database caching wrapper
 * 
 * This class inhabits functionality to get and set cache via a database.
 * 
 * @author		.mario <mario.heiderich@gmail.com>
 *
 * @package		PHPIDS
 * @copyright   2007 The PHPIDS Group
 * @version		SVN: $Id:Database.php 517 2007-09-15 15:04:13Z mario $
 * @since       Version 0.4
 * @link        http://php-ids.org/ 
 */
class IDS_Caching_Database implements IDS_Caching_Interface {

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
     * DBH
     *
     * @var object
     */
    private $handle = NULL;

    /**
     * Holds an instance of this class
     *
     * @var object
     */
    private static $cachingInstance = NULL; 

    /**
     * Constructor
     *
     * Connects to database.
     *
     * @param   string  $type   caching type
     * @param   array   $config caching configuration
     * @return  void
     */
    public function __construct($type, $config) {
        
        $this->type = $type;
        $this->config = $config;
        $this->handle = $this->connect();       
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
            self::$cachingInstance = new IDS_Caching_Database($type, $config);
        }
        
        return self::$cachingInstance;
    }

    /**
     * Writes cache data into the database
     *
     * @param   array   $data
     * @throws  PDOException
     * @return  object  $this
     */
    public function setCache(Array $data) {

    	$handle = $this->handle;
    	
        foreach ($handle->query('SELECT created FROM `'.mysql_escape_string($this->config['table']).'`') as $row) {
        	if ((time()-strtotime($row['created'])) > $this->config['expiration_time']) {
		        
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
     * Returns the cached data
     *
     * Note that this method returns false if either type or file cache is not set
     *
     * @throws  PDOException
     * @return  mixed   cache data or false
     */
    public function getCache() {

    	try{
	    	$handle = $this->handle;
            $result = $handle->prepare(
                'SELECT * FROM '.mysql_escape_string($this->config['table'])
                . ' where type=?'
            );
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
     * Connect to database and return a handle
     *
     * @return  object  dbh
     * @throws  PDOException
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

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */
