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
* Abstract Filter Storage
*
* Class to assure the systems API
*
* @author		 christ1an <ch0012@gmail.com>
*/
abstract class IDS_Filter_Storage_Abstract {

	private $filterSet = array();

	/**
	* Constructor
	*
	* @access	public
	* @param	array
	* @return	void
	*/
	public final function __construct($filterSet = false) {
		if ($filterSet) {
			$this->filterSet = $filterSet;
		}
	}

	/**
	* Sets filter array manually
	*
	* @access	public
	* @param	array
	* @return	mixed	bool true or exception object
	*/
	public final function setFilterSet($filterSet) {
		foreach ($filterSet as $filter) {
			$this->addFilter($filter);
		}
		return $this;
	}

	/**
	* Returns array containing all filters
	*
	* @access	public
	* @return	void
	*/
	public final function getFilterSet() {
		return $this->filterSet;
	}

	/**
	* Adds one particular filter
	*
	* @access	public
	* @param	array
	* @return	mixed	true on success, otherwise exception object
	*/
	public final function addFilter(IDS_Filter_Abstract $filter) {
		$this->filterSet[] = $filter;
		return $this;
	}
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */
