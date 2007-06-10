<?php

/**
 * PHP IDS
 *
 * Requirements: PHP5, SimpleXML, MultiByte Extension (optional)
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
 * Introdusion Dectection System
 *
 * This class provides function(s) to scan incoming data for
 * malicious script fragments and to return an array of possibly
 * intrusive parameters.
 *
 * @author		.mario <mario.heiderich@gmail.com>
 * @author		christ1an <ch0012@gmail.com>
 * @author		Lars Strojny <lstrojny@neu.de>
 *
 * @version		$Id$
 */
class IDS_Monitor {

	private $tags	 = NULL;
	private $request = NULL;
	private $storage = NULL;

	private $report;

	/**
	* This array is meant to define which variables need to be ignored
	* by the php ids - default is the utmz google analytics parameter
	*/
	private $exceptions = array(
		'_utmz'
	);

	/**
	 * Use this array to define the charsets the mb_convert_encoding
	 * has to work with. You shouldn't touch this as long you know
	 * exactly what you do
	 */
	private $charsets = array(
		'UTF-7',
		'ASCII'
	);

	/**
	* Constructor
	*
	* @access   public
	* @param	array
	* @param	object  IDS_Filter_Storage object
	* @param	tags	optional
	* @return   mixed
	*/
	public function __construct(Array $request, IDS_Filter_Storage $storage, Array $tags = null) {
		if (!empty($request)) {
			$this->storage  = $storage;
			$this->request  = $request;
			$this->tags	 	= $tags;
		}

		require_once 'IDS/Report.php';
		$this->report = new IDS_Report;
	}

	/**
	* Runs the detection mechanism
	*
	* @access   public
	* @return   IDS_Report
	*/
	public function run() {
		if(!empty($this->request)){
			foreach ($this->request as $key => $value) {
				$this->iterate($key, $value);
			}
		}

		return $this->getReport();
	}

	/**
	* Iterates through given array and tries to detect
	* suspicious strings
	*
	* @access   private
	* @param	mixed   key
	* @param	mixed   value
	* @return   void
	*/
	private function iterate($key, $value) {
		if (!is_array($value)) {
			if ($filter = $this->detect($key, $value)) {
				require_once 'IDS/Event.php';
				$this->report->addEvent(
					new IDS_Event(
						$key,
						$value,
						$filter
					)
				);
			}
		} else {
			foreach ($value as $subKey => $subValue) {
				$this->iterate(
					$key . '.' . $subKey, $subValue
				);
			}
		}
	}

	/**
	* Checks whether given value matches any of the supplied
	* filter patterns
	*
	* @access   private
	* @param	mixed
	* @param	mixed
	* @return   mixed   false or filter(s) that matched the value
	*/
	private function detect($key, $value) {
		if (!is_numeric($value) && !empty($value)) {

			if (in_array($key, $this->exceptions)) {
				return false;
			}

			$filters = array();
			$filterSet = $this->storage->getFilterSet();
			foreach ($filterSet as $filter) {

				/**
				* In case we have a tag array specified the IDS will only
				* use those filters that are meant to detect any of the given tags
				*/
				if (is_array($this->tags)) {
					if (array_intersect($this->tags, $filter->getTags())) {
						if ($this->prepareMatching($value, $filter)) {
							$filters[] = $filter;
						}
					}
				}

				// here we make use of all filters available
				else {
					if ($this->prepareMatching($value, $filter)) {
						$filters[] = $filter;
					}
				}
			}
			
			return empty($filters) ? false : $filters;
		}
	}

	/**
	* Prepares matching process
	*
	* @access	private
	* @param	string
	* @param	object
	* @return	mixed	prepared value or boolean
	*/
	private function prepareMatching($value, IDS_Filter_Abstract $filter) {

		// use mb_convert_encoding if available
		if (function_exists('mb_convert_encoding')) {
			$value = @mb_convert_encoding($value, 'UTF-8', $this->charsets);
			return $filter->match(urldecode($value));

		// use iconv if available
		} elseif (!function_exists('iconv')) {
			foreach($this->charsets as $charset){
				$value = iconv($this->charsets[0], 'UTF-8', $value);
				if ($filter->match(urldecode($value))) {
					return true;
				}
			}
			
		} else {
			return $filter->match(urldecode($value));
		}

    	return false;
	}

	/**
	* Sets exception array
	*
	* @access   public
	* @param	array
	* @return   void
	*/
	public function setExceptions(Array $exceptions){
		return $this->exceptions = $exceptions;
	}

	/**
	* Returns exception array
	*
	* @access   public
	* @return   array
	*/
	public function getExceptions(){
		return $this->exceptions;
	}

	/**
	* Returns report object providing various functions to
	* work with detected results
	*
	* @access   public
	* @return   IDS_Report
	*/
	public function getReport() {
		return $this->report;
	}

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */
