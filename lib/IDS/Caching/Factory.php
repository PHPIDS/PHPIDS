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
 * Caching factory
 *
 * This class is used as a factory to load the correct concrete caching
 * implementation.
 *
 * @category  Security
 * @package   PHPIDS
 * @author    Christian Matthies <ch0012@gmail.com>
 * @author    Mario Heiderich <mario.heiderich@gmail.com>
 * @author    Lars Strojny <lars@strojny.net>
 * @copyright 2007 The PHPIDS Group
 * @license   http://www.gnu.org/licenses/lgpl.html LGPL
 * @version   Release: $Id:Factory.php 517 2007-09-15 15:04:13Z mario $
 * @link      http://php-ids.org/
 * @since     Version 0.4
 */
class IDS_Caching
{

    /**
     * Factory method
     *
     * @param array  $config the config array
     * @param string $type   the caching type
     * 
     * @return object the caching facility
     */
    public static function factory($config, $type) 
    {
        $object  = false;
        $wrapper = ucfirst($config['caching']);
        $class   = 'IDS_Caching_' . escapeshellcmd($wrapper);
        $path    = dirname(__FILE__) . DIRECTORY_SEPARATOR . 
            escapeshellcmd($wrapper) . '.php';

        if (file_exists($path)) {
            include_once $path;

            if (class_exists($class)) {
                $object = call_user_func(array($class, 'getInstance'), 
                    $type, $config);
            }
        }

        return $object;
    }
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */