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
 * PHPIDS report object
 *
 * The report objects collects a number of events and thereby presents the detected results.
 * It provides a convenient API to work with the results.
 *
 * Note that this class implements Countable, IteratorAggregate and a __toString() method
 *
 * @author		Lars Strojny <lstrojny@neu.de>
 * @author		christ1an <ch0012@gmail.com>
 *
 * @package		PHPIDS
 * @copyright   2007 The PHPIDS Group
 * @version		SVN: $Id:Report.php 517 2007-09-15 15:04:13Z mario $
 * @link        http://php-ids.org/
 */
class IDS_Report implements Countable, IteratorAggregate {

	/**
	 * Event container
	 *
	 * @var array
	 */
	protected $events = array();

	/**
	 * List of affected tags
	 *
     * This list of tags is collected from the collected event objects on demand when
     * IDS_Report->getTags() is called
	 *
	 * @var	array
	 */
	protected $tags = array();

	/**
	 * Impact level
	 *
     * The impact level is calculated on demand by adding the results of the event
     * objects on IDS_Report->getImpact()
	 *
	 * @var integer
	 */
	protected $impact = 0;


	/**
	 * Constructor
	 *
     * @param   array	$events
     * @return  void
	 */
	public function __construct(Array $events = NULL) {
		if ($events) {
			foreach ($events as $event) {
				$this->addEvent($event);
			}
		}
	}

	/**
	 * Adds an IDS_Event object to the report
	 *
	 * @param	object  $event	IDS_Event
	 * @return	object	$this
	 */
	public function addEvent(IDS_Event $event) {
		$this->clear();
		$this->events[$event->getName()] = $event;

		return $this;
	}

	/**
	 * Get event by name
	 *
     * In most cases an event is identified by the key of the variable that contained
     * maliciously appearing content
	 *
	 * @param   scalar	$name
	 * @throws	InvalidArgumentException
	 * @return	mixed	IDS_Event object or false if the event does not exist
	 */
	public function getEvent($name) {
		if (!is_scalar($name)) {
			throw new InvalidArgumentException(
				'Invalid argument type given'
			);
		}

		if ($this->hasEvent($name)) {
			return $this->events[$name];
		}
		
		return false;
	}

	/**
	 * Returns list of affected tags
	 *
	 * @return	array
	 */
	public function getTags() {
		if (!$this->tags) {
			$this->tags = array();

			foreach ($this->events as $event) {
				$this->tags = array_merge(
					$this->tags,
					$event->getTags()
				);
			}

			$this->tags = array_values(
				array_unique($this->tags)
			);
		}
		
		return $this->tags;
	}

    /**
     * Returns total impact
     *
     * Each stored IDS_Event object and its IDS_Filter sub-object are called to calculate
     * the overall impact level of this request
     *
     * @return  integer
     */
	public function getImpact() {
		if (!$this->impact) {
			$this->impact = 0;
			foreach ($this->events as $event) {
				$this->impact += $event->getImpact();
			}
		}

		return $this->impact;
	}

	/**
	 * Checks if a specific event with given name exists
	 *
     * @param   scalar
     * @return  boolean
	 */
	public function hasEvent($name) {
		if (!is_scalar($name)) {
			throw new InvalidArgumentException('Invalid argument given');
		}

		return isset($this->events[$name]);
	}

	/**
	 * Returns total amount of events
	 *
	 * @return	integer
	 */
	public function count() {
		return count($this->events);
	}

	 /**
	 * Return iterator object
	 *
     * In order to provide the possibility to directly iterate over the IDS_Event object
     * the IteratorAggregate is implemented. One can easily use foreach() to iterate through
     * all stored IDS_Event objects.
	 *
	 * @return	Iterator
	 */
	public function getIterator() {
		return new ArrayObject($this->events);
	}

    /**
     * Checks if any events are registered
     *
     * @return  boolean
     */
	public function isEmpty() {
		return empty($this->events);
	}

	/**
	 * Clears calculated/collected values
	 *
	 * @return  void
	 */
	protected function clear() {
		$this->impact = 0;
		$this->tags = array();
	}
	
    /**
     * Directly outputs all available information
     *
     * @return  string
     */
	public function __toString() {
		if (!$this->isEmpty()) {
			$output = '';
			
			$output .= 'Total impact: ' . $this->getImpact() . "<br/>\n";
			$output .= 'Affected tags: ' . join(', ', $this->getTags()) . "<br/>\n";
			
			foreach ($this->events as $event) {
				$output .= "<br/>\nVariable: " . htmlspecialchars($event->getName()) . ' | Value: ' . htmlspecialchars($event->getValue()) . "<br/>\n";
				$output .= 'Impact: ' . $event->getImpact() . ' | Tags: ' . join(', ', $event->getTags()) . "<br/>\n";
				
				foreach ($event as $filter) {
					$output .= 'Description: ' . $filter->getDescription() . ' | ';
					$output .= 'Tags: ' . join(', ', $filter->getTags()) . "<br/>\n";
				}
			}
		}
		
		return isset($output) ? $output : false;
	}
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */
