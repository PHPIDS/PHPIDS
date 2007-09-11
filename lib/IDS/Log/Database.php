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
	DROP TABLE IF EXISTS `intrusions`;
	CREATE TABLE IF NOT EXISTS `intrusions` (
	  `id` int(11) unsigned NOT NULL auto_increment,
	  `name` varchar(128) NOT NULL,
	  `value` text NOT NULL,
	  `page` varchar(255) NOT NULL,
	  `ip` varchar(15) NOT NULL,
	  `impact` int(11) unsigned NOT NULL,
	  `created` datetime NOT NULL,
	  PRIMARY KEY  (`id`)
	) ENGINE=MyISAM ;

 * 
 * 
 *  
 */

require_once 'IDS/Log/Interface.php';

/**
* Database wrapper
*
* This class is designed to store incoming data in
* an sql database and implements the singleton pattern
*
* @author	.mario <mario.heiderich@gmail.com>
*
* @version  $Id$
*/
class IDS_Log_Database implements IDS_Log_Interface {

    private $wrapper	= NULL;
    private $user		= NULL;
    private $password	= NULL;
    private $table      = NULL;
    private $handle		= NULL;
    private $statement	= NULL;
    private $ip         = NULL;
	
    private static $instances = array();

    /**
    * Constructor
    *
    * @param    string
    * @param    string
    * @param    string
    * @access   protected
    * @return   mixed	void or exception object
    */
    protected function __construct($wrapper = false, $user = false, $password = false, $table = false) {
        
    	//determine attackers IP
        $this->ip = ($_SERVER['SERVER_ADDR']!='127.0.0.1')
                ?$_SERVER['SERVER_ADDR']
                :(isset($_SERVER['HTTP_X_FORWARDED_FOR'])
                    ?$_SERVER['HTTP_X_FORWARDED_FOR']
                    :'local/unknown');    	
    	
    	if ($wrapper && $user && $password) {
            $this->wrapper = $wrapper;
            $this->user = $user;
            $this->password = $password;	
            $this->table = $table;
		} else {
            throw new Exception('
				Insufficient connection parameters'
			);
		}

		try {
			$this->handle = new PDO(
				$this->wrapper, 
				$this->user, 
				$this->password
			);
			
			$this->statement = $this->handle->prepare('
				INSERT INTO '.$this->table.' (
					name,
					value,
					page,
					ip,
					impact,
					created
				) 
				VALUES (
					:name,
					:value,
					:page,
					:ip,
					:impact,
					now()
				)
			');    	                                       
			
		} catch (PDOException $e) {
            die('PDOException: ' . $e->getMessage());    	
		}
    }

    /**
    * Returns an instance of this class
    *
    * @param    string
    * @param    string
    * @param    string
    * @access   public
    * @return   object
    */
    public static function getInstance($wrapper, $user, $password, $table) {
        if (!isset(self::$instances[$wrapper])) {
            self::$instances[$wrapper] = new IDS_Log_Database(
                $wrapper,
                $user,
                $password, 
                $table
            );
        }

        return self::$instances[$wrapper];
    }

    /**
    * Just for the sake of completeness
    * of a correct singleton pattern
    */
    private function __clone() { }

    /**
    * Stores given data into database
    *
    * @param    object
    * @access   public
    * @return   mixed   bool true on success or exception object on failure
    */
    public function execute(IDS_Report $data) {
        
        foreach ($data as $event) {
        	$page	= isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        	$ip		= $this->ip;
        	
            $this->statement->bindParam('name', $event->getName());
            $this->statement->bindParam('value', $event->getValue());
            $this->statement->bindParam('page', $page);
            $this->statement->bindParam('ip', $ip);
            $this->statement->bindParam('impact', $data->getImpact());
            
            if (!$this->statement->execute()) { 
            	
            	$info = $this->statement->errorInfo();
                throw new Exception($this->statement->errorCode() . ', 
                ' . $info[1] . ', ' . $info[2]);     
            }
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