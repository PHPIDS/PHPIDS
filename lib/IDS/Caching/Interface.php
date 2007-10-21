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
 * @package    PHPIDS
 */

/**
 * Caching wrapper interface
 * 
 * @author        .mario <mario.heiderich@gmail.com>
 *
 * @package        PHPIDS
 * @copyright   2007 The PHPIDS Group
 * @version        SVN: $Id:Interface.php 517 2007-09-15 15:04:13Z mario $
 * @since       Version 0.4
 * @link        http://php-ids.org/
 */
interface IDS_Caching_Interface {
    public function setCache(array $data);
    public function getCache();
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */