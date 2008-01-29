<?php

/**
 * PHPIDS
 * 
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
 * PHP version 5.1.6+
 * 
 * @category Security
 * @package  PHPIDS
 * @author   Mario Heiderich <mario.heiderich@gmail.com>
 * @author   Christian Matthies <ch0012@gmail.com>
 * @author   Lars Strojny <lars@strojny.net>
 * @license  http://www.gnu.org/licenses/lgpl.html LGPL
 * @link     http://php-ids.org/
 */

/**
 * Framework initiation
 *
 * This class is used for the purpose to initiate the framework and inhabits
 * functionality to parse the needed configuration file.
 *
 * @category  Security
 * @package   PHPIDS
 * @author    Christian Matthies <ch0012@gmail.com>
 * @author    Mario Heiderich <mario.heiderich@gmail.com>
 * @author    Lars Strojny <lars@strojny.net>
 * @copyright 2007 The PHPIDS Groupup
 * @license   http://www.gnu.org/licenses/lgpl.html LGPL
 * @version   Release: $Id:Init.php 517 2007-09-15 15:04:13Z mario $
 * @link      http://php-ids.org/
 * @since     Version 0.4
 */
class IDS_Init
{

    /**
     * Holds config settings
     *
     * @var array
     */
    public $config = null;

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
    private $configPath = null;

    /**
     * Constructor
     *
     * Includes needed classes and parses the configuration file
     *
     * @param string $configPath the path to the config file
     * 
     * @return object $this
     */
    private function __construct($configPath) 
    {

        include_once 'IDS/Monitor.php';
        include_once 'IDS/Filter/Storage.php';

        $this->setConfigPath($configPath);
        $this->config = parse_ini_file($this->configPath, true);

        return $this;
    }

    /**
     * Permitting to clone this object
     *
     * For the sake of correctness of a singleton pattern, this is necessary
     * 
     * @return void
     */
    public final function __clone() 
    {
    }

    /**
     * Returns an instance of this class. Also a PHP version check 
     * is being performed to avoid compatibility problems with PHP < 5.1.6
     *
     * @param string $configPath the path to the config file
     * 
     * @return object
     */
    public static function init($configPath) 
    {
        if(!function_exists('phpversion') || phpversion() < '5.1.6') {
            throw new Exception(
                'PHP version has to be equal or higher than 5.1.6 or 
                PHP version couldn\'t be determined'
            );	        
        }
        
        if (!isset(self::$instances[$configPath])) {
            self::$instances[$configPath] = new IDS_Init($configPath);
        }

        return self::$instances[$configPath];
    }

    /**
     * Sets the path to the configuration file
     *
     * @param string $path the path to the config
     * 
     * @throws Exception if file not found
     * @return void
     */
    public function setConfigPath($path) 
    {
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
     * @return string
     */
    public function getConfigPath() 
    {
        return $this->configPath;
    }

    /**
     * Merges new settings into the exsiting ones or overwrites them
     *
     * @param array   $config    the config array
     * @param boolean $overwrite config overwrite flag
     * 
     * @return void
     */
    public function setConfig(array $config, $overwrite = false) 
    {

        if ($overwrite) {
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
    public function getConfig() 
    {

        return $this->config;
    }
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */
