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
* Filter Storage Class
* 
* This class provides various default functions for gathering filter 
* patterns to be used later on by the IDS.
*
* In case new methods need to be implemented, 
* Filter_Storage_Abstract::addFilter() can be used to modify the
* filter set array.
*
* @author	christ1an <ch0012@gmail.com>
*/
class IDS_Filter_Storage extends IDS_Filter_Storage_Abstract {

	/**
	* Loads filters from Json file via ext/json
	*
	* @access	public
	* @param	mixed	string or filename
	* @return	mixed	true on success, otherwise exception object
	*/
	public function getFilterFromJson($source) {
		if (extension_loaded('Json')) {	
			
			if (file_exists($source)) {
				$source = file_get_contents($source);
			} else {
				throw new Exception(
					'JSON data could not be loaded.' .
					'Make sure you specified the correct path.'
				);					
			}
			
			$filters = json_decode($source);
			if(!empty($filters)){
				foreach ($filters as $filter) {
					$this->addFilter(
						array(
							'rule'		=> $filter->rule,
							'impact'	=> $filter->impact,
							'tags'		=> $filter->tags
						)
					);
				}	
			}	
		
		} else {
			throw new Exception(
				'ext/json not loaded.'
			);
		}
		
		return $this;
	}

	/**
	* Loads filters from XML file via SimpleXML
	*
	* @access	public
	* @param	mixed	string or filename
	* @return	mixed	true on success, otherwise exception object
	*/
	public function getFilterFromXML($source) {
		if (extension_loaded('SimpleXML')) {
			
			if (file_exists($source)) {
				$filters = simplexml_load_file($source);
			} else {
				$filters = simplexml_load_string($source);
			}
							
			if ($filters === false) {
				throw new Exception(
					'XML data could not be loaded.' .
					'Make sure you specified the correct path.'
				);
			}

			if(!empty($filters->filter)){
				foreach ($filters->filter as $filter) {
					$rule	= (string) $filter->rule;
					$impact = (string) $filter->impact;
					$tags	= array_values((array) $filter->tags);
					$description = (string)$filter->description;

					require_once dirname(__FILE__) . '/filter.php';
					$this->addFilter(
						new IDS_Filter_Regexp(
							$rule,
							$description,
							(array) $tags[0],
							(int) $impact
						)
					);
				}
			}
			
		} else {
			throw new Exception(
				'SimpleXML not loaded.'
			);
		}
		
		return $this;
	}

}


/**
* Abstract Filter Storage
* 
* Class to assure the systems API
*
* @package		 profiles
* @subpackage	 models
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
