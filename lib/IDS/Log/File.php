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

require_once 'IDS/Log/Interface.php';

/**
 * File logging wrapper
 *
 * The file wrapper is designed to store data into a flatfile. It implements the
 * singleton pattern.
 *
 * @author		christ1an <ch0012@gmail.com>
 *
 * @package		PHPIDS
 * @copyright	2007 The PHPIDS Group
 * @version		SVN: $Id:File.php 517 2007-09-15 15:04:13Z mario $
 * @link		http://php-ids.org/
 */
class IDS_Log_File implements IDS_Log_Interface {
    
    /**
     * Path to the log file
     *
     * @var string
     */
    private $logfile = NULL;

    /**
     * Instance container
     *
     * Due to the singleton pattern this class allows to initiate only one instance
     * for each file.
     *
     * @var array
     */
    private static $instances = array();

    /**
     * Holds current remote address
     *
     * @var string
     */
	private $ip = 'local/unknown';

    /**
     * Constructor
     *
     * @param   string  $logfile    path to the log file
     * @return  void
     */
	protected function __construct($logfile) {
		
		// determine correct IP address
		if ($_SERVER['REMOTE_ADDR'] != '127.0.0.1') {
			$this->ip = $_SERVER['REMOTE_ADDR'];
		} elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$this->ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}

		$this->logfile = dirname(__FILE__) . '/../../' . $logfile;
	}

    /**
     * Returns an instance of this class
     *
     * This method allows the passed argument to be either an instance of IDS_Init or
     * a path to a log file. Due to the singleton pattern only one instance for each file
     * can be initiated.
     *
     * @param   mixed   $config IDS_Init or path to a file
     * @return  object  $this 
     */
	public static function getInstance($config) {
		if ($config instanceof IDS_Init) {
			$logfile = $config->config['Logging']['path'];
		} elseif (is_string($config)) {
			$logfile = $config;
		}
	
		if (!isset(self::$instances[$logfile])) {
			self::$instances[$logfile] = new IDS_Log_File($logfile);
		}

		return self::$instances[$logfile];
	}

	/**
     * Permitting to clone this object
     *
     * For the sake of correctness of a singleton pattern, this is necessary
     */
    private function __clone() { }

    /**
     * Prepares data
     *
     * Converts given data into a format that can be stored into a file. You might
     * edit this method to your requirements.
     *
     * @param   mixed   $data
     * @return  string
     */
	protected function prepareData($data) {
	
		$format = '"%s",%s,%d,"%s","%s","%s"';
		
		$attackedParameters = '';
		foreach ($data as $event) {
			$attackedParameters .= $event->getName() . '=' . rawurlencode($event->getValue()) . ' ';
		}
		
		$dataString = sprintf(
			$format,
			$this->ip,
			date('c'),
			$data->getImpact(),
			join(' ', $data->getTags()),
			trim($attackedParameters),
			urlencode($_SERVER['REQUEST_URI'])
		);
		
		return $dataString;
	}

    /**
     * Stores given data into a file
     *
     * @param   object  $data   IDS_Report
     * @throws  Exception
     * @return  mixed
     */
	public function execute(IDS_Report $data) {

	    /*
         * In case the data has been modified before it might  be necessary to convert
         * it to string since we can't store array or object into a file
		 */
		$data = $this->prepareData($data);

		if (is_string($data)) {
			
			if (file_exists($this->logfile)) {
				$data = trim($data);

				if (!empty($data)) {
					if (is_writable($this->logfile)) {
					
                    	$handle = fopen($this->logfile, 'a');
						fwrite($handle, $data . "\n");
						fclose($handle);
						
                    } else {
                        throw new Exception(
                            'Please make sure that ' . $this->logfile . ' is writeable.'
                        );                        
                    }
				}
			} else {
				throw new Exception(
					'Given file does not exist. Please make sure the  
                    logfile is present in the given directory.'
				);
			}
		} else {
			throw new Exception(
				'Please make sure that data returned by
				IDS_Log_File::prepareData() is a string.'
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