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
 * PHP IDS report object
 *
 * The report objects collects a number of events in order to present the
 * filter results. It provides a convenient API to work with the results.
 *
 * @author Lars Strojny <lstrojny@neu.de>
 */
class IDS_Report implements Countable, IteratorAggregate {

	/**
	 * List of events
	 *
	 * @var array
	 */
	protected $_events = array();

	/**
	 * List of tags
	 *
	 * This list of tags is collected from the collected event objects
	 * on demand (when IDS_Report->getTags() is called)
	 *
	 * @var	array
	 */
	protected $_tags = false;

	/**
	 * Impact level
	 *
	 * The impact level is calculated on demand (by adding the results of
	 * the event objects on IDS_Report->getImpact())
	 *
	 * @var	integer
	 */
	protected $_impact = false;

	/**
	 * Constructor
	 *
	 * @param	array $events List of IDS_Event objects
	 */
	public function __construct(Array $events = NULL) {
		if ($events !== null) {
			foreach ($events as $event) {
				$this->addEvent($event);
			}
		}
	}

	/**
	 * Add an IDS_Event object to the report
	 *
	 * @param	IDS_Event $event
	 * @return	$this
	 */
	public function addEvent(IDS_Event $event) {
		$this->_clear();
		$this->_events[$event->getName()] = $event;

		return $this;
	}

	/**
	 * Get event (by name)
	 *
	 * Every event is named by its source name. You can get a specific event by
	 * its name with this method.
	 *
	 * @param	scalar $name
	 * @throws	InvalidArgumentException
	 * @return	IDS_Event|false
	 */
	public function getEvent($name) {
		if (!is_scalar($name)) {
			throw new InvalidArgumentException('Invalid argument type given');
		}

		if ($this->hasEvent($name)) {
			return $this->_events[$name];
		}

		return false;
	}
   
	/**
	 * Get list of tags
	 *
	 * Returns a list of collected tags from all of the IDS_Event sub-objects
	 *
	 * @return	array
	 */
	public function getTags() {
		if (!$this->_tags) {
			$this->_tags = array();
			
			foreach ($this->_events as $event) {
				$this->_tags = array_merge(
					$this->_tags, 
					$event->getTags()
				);
			}
			
			$this->_tags = array_values(
				array_unique($this->_tags)
			);
		}
		return $this->_tags;
	}
 
	 /**
	 * Get impact level
	 *
	 * Return calculated impact level. Every IDS_Event sub object and
	 * its IDS_Filter objects are used to calculate the overall impact
	 * level.
	 *
	 * @return	integer
	 */
	public function getImpact() {
		if (!$this->_impact) {
			$this->_impact = 0;
			foreach ($this->_events as $event) {
				$this->_impact += $event->getImpact();
			}
		}

		return $this->_impact;
	}

	/**
	 * Event with name $name is existant?
	 *
	 * @var	scalar $name
	 */
	public function hasEvent($name) {
		if (!is_scalar($name)) {
			throw new InvalidArgumentException('Invalid argument given');
		}

		return isset($this->_events[$name]);
	}

	/**
	 * Number of events
	 *
	 * Returns the number of events contained in the IDS_Report object. Implements
	 * interface Countable
	 *
	 * @return	integer
	 */
	public function count() {
		return count($this->_events);
	}
 
	 /**
	 * Return iterator object
	 *
	 * In order to provide the possibility to directly iterate over
	 * the IDS_Event object the IteratorAggregate is implemented. So
	 * a user can easily use foreach () to iterate through all the 
	 * IDS_Event objects
	 *
	 * @return	Iterator
	 */
	public function getIterator() {
		return new ArrayObject($this->_events);
	}
 
	 /**
	 * Report is empty?
	 *
	 * @return	bool
	 */
	public function isEmpty() {
		return empty($this->_events);
	}

	/**
	 * Clear calculated/collected values
	 *
	 * @return	void
	 */
	protected function _clear() {
		$this->_impact = false;
		$this->_tags = false;
	}
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */

