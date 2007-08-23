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

require_once 'IDS/Filter/Abstract.php';

/**
 * Regex filter class
 *
 * The filter class based on regular expression matching is the default
 * filter class used in PHP IDS.
 *
 * @author Lars Strojny <lstrojny@neu.de>
 *
 * @version	$Id$
 */
class IDS_Filter_Regex extends IDS_Filter_Abstract {

	/**
	 * PCRE flags
	 *
	 * @var string
	 */
	protected static $flags = 'ims';

	/**
	 * Set PCRE flags
	 *
	 * @param string $flags Regular expression modifier flags
	 * @return void
	 */
	public static function setFlags($flags)
	{
		self::$flags = $flags;
	}

	/**
	 * Returns PCRE flags
	 *
	 * @return string
	 */
	public static function getFlags()
	{
		return self::$flags;
	}

	/**
	 * Match method
	 *
	 * IDS_Filter_Regex->match() used preg_match() to match the rule against
	 * the given string.
	 *
	 * @return	bool Filter matched?
	 */
	public function match($string) {
		if (!is_string($string)) {
			throw new Exception('
				Invalid argument. Exptected a string, got ' . gettype($string)
			);
		}

		return (bool) preg_match('/' . $this->getRule() . '/' . self::getFlags(), $string);
	}
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */
