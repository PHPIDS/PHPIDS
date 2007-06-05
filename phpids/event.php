<?php

/**
 * PHP IDS
 * 
 * Requirements: PHP5, SimpleXML, MultiByte Extension (optional)
 *  
 * Copyright 2007 Mario Heiderich for Ormigo 
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy 
 * of this software and associated documentation files (the "Software"), to deal 
 * in the Software without restriction, including without limitation the rights to use, 
 * copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the 
 * Software, and to permit persons to whom the Software is furnished to do so, 
 * subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in 
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR 
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, 
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL 
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, 
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN 
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

/**
 * This file is hosted on Google Code and can be 
 * discussed on Google Groups
 * 
 * http://code.google.com/p/phpids/ 
 * http://groups.google.de/group/php-ids/
 * 
 */

/**
 * IDS event object
 *
 * This class represents a certain event which has been occured while applying
 * the filters to the given data. It aggregates a bunch of IDS_Filter_Abstract
 * implementations and is a assembled in IDS_Report.
 *
 * @author	Lars Strojny <lstrojny@neu.de>
 */
class IDS_Event implements Countable, IteratorAggregate {
	
	/**
	 * Event name
	 *
	 * @var scalar
	 */
	protected $_name	= null;

	/**
	 * Value the filter has been applied
	 *
	 * @var scalar
	 */
	protected $_value   = null;

	/**
	 * List of filters
	 *
	 * @var array
	 */
	protected $_filters = array();

	/**
	 * Computed impact
	 *
	 * @var integer|bool
	 */
	protected $_impact  = false;

	/**
	 * Assembled tags
	 *
	 * @var array
	 */
	protected $_tags	= false;

	/**
	 * Generate a new IDS event
	 *
	 * You need to pass the event name (most of the time the name of the key in the
	 * array you have filtered), the value the filters have been applied on and a
	 * list of filters.
	 *
	 * @param scalar $name
	 * @param scalar $value
	 * @param value $filters
	 */
	public function __construct($name, $value, array $filters) {
		if (!is_scalar($name)) {
			throw new InvalidArgumentException('Expected $name to be a scalar, '
				. gettype($name) . ' given');
		}
		$this->_name = $name;

		if (!is_scalar($value)) {
			throw new InvalidArgumentException('Expected $value to be a scalar, '
				. gettype($value) . ' given');
		}
		$this->_value = $value;

		foreach ($filters as $filter) {
			if (!$filter instanceof IDS_Filter_Abstract) {
				throw new InvalidArgumentException(
					'Filter must be derived from IDS_Filter_Abstract');
			}
			$this->_filters[] = $filter;
		}
	}

	/**
	 * Get event name
	 *
	 * Returns the name of the event (most of the time the name of the filtered
	 * key of the array)
	 *
	 * @return scalar
	 */
	public function getName() {
		return $this->_name;
	}

	/**
	 * Get event value
	 *
	 * Returns the value which has been passed to the filters
	 *
	 * @return scalar
	 */
	public function getValue() {
		return $this->_value;
	}

	/**
	 * Get computed impact
	 *
	 * Returns the overal impact of all filters
	 *
	 * @return integer
	 */
	public function getImpact() {
		
		// Impact is calculated?
		if (!$this->_impact) {
			$this->_impact = 0;
			foreach ($this->_filters as $filter) {
				$this->_impact += $filter->getImpact();
			}
		}
		
		return $this->_impact;
	}

	/**
	 * Get assembled tags
	 *
	 * Collects all the tags of the filters
	 *
	 * @return array
	 */
	public function getTags() {
		if (!$this->_tags) {
			$this->_tags = array();
			foreach ($this->getFilters() as $filter) {
				$this->_tags = array_merge(
					$this->_tags, 
					$filter->getTags()
				);
			}
			
			$this->_tags = array_values(
				array_unique($this->_tags)
			);
		}
		
		return $this->_tags;
	}

	/**
	 * Get list of filters
	 *
	 * @return array
	 */
	public function getFilters() {
		return $this->_filters;
	}

	/**
	 * Get number of filters
	 *
	 * To implement interface Countable this returns the number of filters
	 * appended.
	 *
	 * @return integer
	 */
	public function count() {
		return count($this->getFilters());
	}

	/**
	 * IteratorAggregate iterator getter
	 *
	 * Returns a iterator to iterate over the appended filters.
	 *
	 * @return Iterator|IteratorAggregate
	 */
	public function getIterator() {
		return new ArrayObject($this->getFilters());
	}
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */
