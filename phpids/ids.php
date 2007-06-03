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
	 * Introdusion Dectection System
	 *
	 * This class provides function(s) to scan incoming data for
	 * malicious script fragments and to return an array of possibly
	 * intrusive parameters.
	 *
	 * @author   .mario <mario.heiderich@gmail.com>
	 * @author   christ1an <ch0012@gmail.com>
	 * @author   lars <lars@strojny.net>
	 */
	class IDS_Monitor {
		
		private $tags	 = NULL;
		private $request = NULL;
		private $storage = NULL;
		
		private $report;
		private $modifier = 'iDs';
		
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
			
			require_once dirname(__FILE__) . '/report.php';
			$this->report = new IDS_Report;
		}
		
		/**
		* Runs the detection mechanism
		*
		* @access   public
		* @return   array
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
					require_once dirname(__FILE__) . '/event.php';
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
		* Checks whether given value matches any of the
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
							$filters = $this->prepareMatching(
								$value,
								$filter
							);
						}
					} 
					
					// here we make use of all filters available
					else {
						$filters = $this->prepareMatching(
							$value,
							$filter
						);
					}
				}
				
				return $filters;
			}
		}
		
		/**
		* Prepares matching process
		*
		* @access	private
		* @param	string
		* @param	object
		* @return	array
		*/
		private function prepareMatching($value, $filter) {
	
			$filters = array();
			
			// use mb_convert_encoding if available
			if (function_exists('mb_convert_encoding')) {
				$value = @mb_convert_encoding($value, 'UTF-8', $this->charsets);  
				if ($filter->match(urldecode($value))) {
					$filters[] = $filter;
				}
	
			// use iconv if available
			} elseif (!function_exists('iconv')) {
				foreach($this->charsets as $charset){
					$value = iconv($this->charsets[0], 'UTF-8', $value);
					if ($filter->match(urldecode($value))) {
						$filters[] = $filter;
					}                                
				}        
			} else {
				if ($filter->match(urldecode($value))) {
					$filters[] = $filter;
				}                        	
			}
			
			return $filters;	
		}
		
		/**
		* Sets exception array
		*
		* @access   public
		* @param	array
		* @return   void
		*/
		public function setExceptions(array $exceptions){
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
		* Sets pattern modifier
		*
		* @access   public
		* @param	string
		* @return   void
		*/
		public function setModifier($modifier) {
			return $this->modifier = $modifier;
		}
	
		/**
		* Returns pattern modifier
		*
		* @access   public
		* @return   string
		*/
		public function getModifier() {
			return $this->modifier;
		}
		
		/**
		* Returns result array containing suspicious
		* variables and additionally the filter that detected
		* those
		*
		* @access   public
		* @return   array
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
