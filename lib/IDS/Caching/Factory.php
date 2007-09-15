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
 * Caching factory
 * 
 * This class is used as a factory to load the correct concrete caching
 * implementation.
 *
 * @author		.mario <mario.heiderich@gmail.com>
 * @author		christ1an <ch0012@gmail.com>
 *
 * @package		PHPIDS
 * @copyright   2007 The PHPIDS Group
 * @version		SVN: $Id:Factory.php 517 2007-09-15 15:04:13Z mario $
 * @since       Version 0.4
 * @link        http://php-ids.org/
 */
class IDS_Caching {
    
    /**
     * Factory method
     *
     * @param   array   $config
     * @param   string  $type
     */
    public static function factory($config, $type) {
        $object = false;

        $wrapper    = ucfirst($config['caching']);
        $class      = 'IDS_Caching_' . $wrapper;
        $path       = 'IDS/Caching/' . $wrapper . '.php';
        
        if (file_exists(dirname(__FILE__) . '/../../' . $path)) {
            require_once $path;

            if (class_exists($class)) {
                $object = call_user_func(array($class, 'getInstance'), $type, $config);
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