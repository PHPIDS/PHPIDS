<?php

/**
 * PHP IDS
 * 
 * Requirements: PHP5, SimpleXML, MultiByte Extension (optional)
 *
 * Copyright (c) 2007 PHPIDS (http://phpids.org)
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
 * Regexp filter class
 *
 * The filter class based on regular expression matching is the default
 * filter class used in PHP IDS.
 *
 * @author Lars Strojny <lstrojny@neu.de>
 */
class IDS_Filter_Regexp extends IDS_Filter_Abstract {

	/**
	 * Match method
	 *
	 * IDS_Filter_Regexp->match() used preg_match() to match the rule against
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
		
		return (bool) preg_match('/' . $this->getRule() . '/', $string);
	}
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */
