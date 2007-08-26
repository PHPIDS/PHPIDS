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
 * PHPIDS report object
 *
 * The report objects collects a number of events and thereby presents the
 * filtered results. It provides a convenient API to work with the results.
 *
 * @author	Lars Strojny <lstrojny@neu.de>
 * @version	$Id$
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
	 * This list of tags is collected from the collected event objects
	 * on demand when IDS_Report->getTags() is called
	 *
	 * @var	array
	 */
	protected $tags = false;

	/**
	 * Impact level
	 *
	 * The impact level is calculated on demand by adding the results of
	 * the event objects on IDS_Report->getImpact()
	 *
	 * @var	integer
	 */
	protected $impact = false;


	/**
	 * Constructor
	 *
	 * @param	array	$events
	 */
	public function __construct(Array $events = NULL) {
		if ($events !== NULL) {
			foreach ($events as $event) {
				$this->addEvent($event);
			}
		}
	}

	/**
	 * Add an IDS_Event object to the report
	 *
	 * @param	object	IDS_Event
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
	 * In most cases an event is identified by the key of the variable
	 * that contained maliciously looking content
	 *
	 * @param	scalar	$name
	 * @throws	InvalidArgumentException
	 * @return	mixed	IDS_Event object or false
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
	 * Get list of tags
	 *
	 * Returns a list of affected tags of all stored
	 * IDS_Event objects
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
	* Get impact level
	*
	* Each IDS_Event object and its IDS_Filter sub objects are called
	* to calculate the overall impact level of this request
	*
	* @return	integer
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
	 * Checks if a specific event with given name
	 * exists
	 *
	 * @var	scalar
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
	 * Implements interface Countable
	 *
	 * @return	integer
	 */
	public function count() {
		return count($this->events);
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
		return new ArrayObject($this->events);
	}

	 /**
	 * Checks whether or not any reports exist
	 *
	 * @return	bool
	 */
	public function isEmpty() {
		return empty($this->events);
	}

	/**
	 * Clear calculated/collected values
	 *
	 * @return	void
	 */
	protected function clear() {
		$this->impact = false;
		$this->tags = false;
	}
	
	/**
	* Displays available information
	*
	* @return	string
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

/**
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */