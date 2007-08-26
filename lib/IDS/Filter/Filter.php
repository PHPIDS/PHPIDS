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
 * Main filter class
 *
 * Each object of this class serves as a container for a specific
 * filter. The object provides methods to get information about the
 * particular filter and also to match an arbitrary string against it.
 *
 * @author	Lars Strojny <lstrojny@neu.de>
 * @author	christ1an <ch0012@gmail.com>
 * @version	$Id: Abstract.php 391 2007-08-23 21:57:38Z mario $
 */
class IDS_Filter {

	/**
	 * Filter rule
	 *
	 * @var	mixed
	 */
	protected $rule;

	/**
	 * List of tags of the filter
	 *
	 * @var	array
	 */
	protected $tags = array();

	/**
	 * Filter impact level
	 *
	 * @var	integer
	 */
	protected $impact = 0;
	
	/**
	 * Filter description
	 *
	 * @var	string
	 */
	protected $description;

	/**
	 * Constructor
	 *
	 * @param	mixed	$rule			filter rule
	 * @param	string	$description	filter description
	 * @param	array	$tags			list of tags
	 * @param	integer $impact			filter impact level
	 */
	public function __construct($rule, $description, Array $tags, $impact) {
		$this->rule 	= $rule;
		$this->tags 	= $tags;
		$this->impact 	= $impact;		
		$this->description = $description;
	}

	/**
	 * Match method
	 *
	 * Matches given string against the filter rule the specific
	 * object of this class represents 
	 *
	 * @return	bool	true if filter matched, otherwise false
	 */
	public function match($string) {
		if (!is_string($string)) {
			throw new Exception('
				Invalid argument. Expected a string, received ' . gettype($string)
			);
		}

		return (bool) preg_match('/' . $this->getRule() . '/ims', $string);
	}

	/**
	 * Returns filter description
	 *
	 * @return	string
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * Return list of affected tags
	 *
	 * Each filter rule is concerned with a certain kind
	 * of attack vectors. This method returns those affected
	 * kinds.
	 *
	 * @return	array	List of tags
	 */
	public function getTags() {
		return $this->tags;
	}

	/**
	 * Returns filter rule
	 *
	 * @return	string
	 */
	public function getRule() {
		return $this->rule;
	}

	/**
	 * Get filter impact level
	 *
	 * @return	integer
	 */
	public function getImpact() {
		return $this->impact;
	}
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */