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
* Abstract Filter Storage
*
* Class to assure the systems API
*
* @author	christ1an <ch0012@gmail.com>
* @version	$Id$
*/
abstract class IDS_Filter_Storage_Abstract {

	protected $filterPath = NULL;
	
	protected $caching = NULL;
	
	/**
	* Constructor
	*
	* @access	public
	* @param	object
	* @return	void
	*/
	public final function __construct(IDS_Init $init) {
		if ($init->config) {
			$this->filterPath = dirname(__FILE__) . '/../../../' . 
			    $init->config['IDS_Basic']['filter_path'];
			
			if(isset($init->config['IDS_Caching']['caching']) 
			    && $init->config['IDS_Caching']['caching'] != 'none') {
				$this->caching = $init->config['IDS_Caching'];
			}
			
			if($init->config['IDS_Basic']['filter_type'] == 'xml') {
			    $this->getFilterFromXML();
			} elseif($init->config['IDS_Basic']['filter_type'] == 'json') {
			    $this->getFilterFromJson(); 	
			} else {
				throw new Exception('Unknown filter type!');
			}
		}
	}

    /**
    * Sets filter array manually
    *
    * @access   public
    * @param    array
    * @return   object
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
    * @access   public
    * @return   void
    */
    public final function getFilterSet() {
        return $this->filterSet;
    }	
	
	/**
	* Adds one particular filter
	*
	* @access	public
	* @param	object
	* @return	object
	*/
	public final function addFilter(IDS_Filter $filter) {
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