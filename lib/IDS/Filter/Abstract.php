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

/**
 * Abstract filter class
 *
 * A basic implementation of a filter object
 *
 * @author	Lars Strojny <lstrojny@neu.de>
 */
abstract class IDS_Filter_Abstract {

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
	 * Constructor
	 *
	 * @param	mixed $rule				Filter rule
	 * @param	string $description		Filter description
	 * @param	array $tags				List of tags
	 * @param	integer $impact			Filter impact level
	 */
	public function __construct($rule, $description, Array $tags, $impact) {
		$this->_description = $description;
		$this->_rule = $rule;
		$this->_tags = $tags;
		$this->_impact = $impact;
	}

	/**
	 * Abstract match method
	 *
	 * The concrete match process which returns a boolean to inform
	 * about a match
	 *
	 * @return	bool
	 */
	abstract public function match($string);

	/**
	 * Get filter description
	 *
	 * @return	string	Filter description
	 */
	public function getDescription() {
		return $this->_description;
	}

	/**
	 * Return list of tags
	 *
	 * @return	array	List of tags
	 */
	public function getTags() {
		return $this->_tags;
	}

	/**
	 * Return filter rule
	 *
	 * @return	mixed	Filter rule
	 */
	public function getRule() {
		return $this->_rule;
	}

	/**
	 * Get filter impact level
	 *
	 * @return	integer	Impact level
	 */
	public function getImpact() {
		return $this->_impact;
	}
}
