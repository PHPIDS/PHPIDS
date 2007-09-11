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

require_once 'IDS/Filter/Storage/Abstract.php';

/**
* Filter Storage Class
*
* This class provides various default functions for gathering filter
* patterns to be used later on by the IDS.
*
* @author   christ1an <ch0012@gmail.com>
* @version  $Id:Storage.php 391 2007-08-23 21:57:38Z mario $
*/
class IDS_Filter_Storage extends IDS_Filter_Storage_Abstract {

    /**
    * Loads filters from XML file via SimpleXML
    *
    * @access   public
    * @param    mixed   string or filename
    * @return   object  $this on success, otherwise exception object
    * @throws   Exception in case the XML data can't be found or parsed 
    */
    public function getFilterFromXML() {

    	if (extension_loaded('SimpleXML')) {

    		$filters = false;
    		
            if ($this->caching) {
            	require_once 'IDS/Caching/Factory.php';
            	$cache = IDS_Caching::createCaching(
                            $this->caching, 
                            'storage'
            	         );
                            
                if($cache) {
                	
                	// get cache
                    $filters = $cache->getCache();
                }
            } 
            
            if(!$filters || !$cache) {
            	
            	if (file_exists($this->filterPath)) {
                	
                	if (LIBXML_VERSION >= 20621) {
                    	$filters = simplexml_load_file(
							$this->filterPath, 
							NULL, 
							LIBXML_COMPACT
						);
                   } else {
                       $filters = simplexml_load_file($this->filterPath);
                   }
                }        
            }
            
            if (empty($filters)) {
                throw new Exception(
                    'XML data could not be loaded.' .
                    'Make sure you specified the correct path.'
                );
            }

            $data = array();
            $nocache = $filters instanceof SimpleXMLElement;
            $filters = $nocache ? $filters->filter : $filters;

            require_once 'IDS/Filter/Filter.php';
            
            foreach ($filters as $filter) {

                $rule   = $nocache ? (string) $filter->rule : $filter['rule'];
                $impact = $nocache ? (string) $filter->impact : $filter['impact'];
                $tags   = $nocache ? array_values((array) $filter->tags) : $filter['tags'];
                $description = $nocache ? (string) $filter->description : $filter['description'];                       

                $data[] = array(
					'rule'		=> $rule, 
					'impact'	=> $impact, 
					'tags'		=> $tags, 
					'description' => $description
				);

                $this->addFilter(
                    new IDS_Filter(
                        $rule,
                        $description,
                        (array) $tags[0],
                        (int) $impact
                    )
                );
            }
            
            // write cache
            if($cache) {
                $cache->setCache($data);
            }             

        } else {
            throw new Exception(
                'SimpleXML not loaded.'
            );
        }

        return $this;
    }


    /**
    * Loads filters from Json file via ext/Json
    *
    * @access   public
    * @param    mixed   string or filename
    * @return   mixed   true on success, otherwise exception object
    * @throws   Exception in case the JSON data can't be found or parsed
    */
    public function getFilterFromJson() {
        
    	if (extension_loaded('Json')) { 

            $filters = false;
            
            if ($this->caching) {
                require_once 'IDS/Caching/Factory.php';
                $cache = IDS_Caching::createCaching(
                            $this->caching, 
                            'storage'
                         );
                            
                if($cache) {
                    
                    // get cache
                    $filters = $cache->getCache();
                }
            }    		
    		
            if(!$filters || !$cache) {
                
                if (file_exists($this->filterPath)) {
                	$filters = json_decode(file_get_contents($this->filterPath));
                } else {
	                throw new Exception(
	                    'JSON data could not be loaded.' .
	                    'Make sure you specified the correct path.'
	                );
                }                  
            }

            if ($filters == null) {
            	throw new Exception(
                    'JSON data could not be loaded.' .
                    'Make sure you specified the correct path.'
                );
            }            

            $data = array();
            $nocache = !is_array($filters);
            $filters = $nocache ? $filters->filters->filter : $filters;

            require_once 'IDS/Filter/Filter.php';
            
            foreach ($filters as $filter) {
            	
                $rule   = $nocache ? (string) $filter->rule : $filter['rule'];
                $impact = $nocache ? (string) $filter->impact : $filter['impact'];
                $tags   = $nocache ? array_values((array) $filter->tags) : $filter['tags'];
                $description = $nocache ? (string) $filter->description : $filter['description'];                       

                $data[] = array(
                    'rule'      => $rule, 
                    'impact'    => $impact, 
                    'tags'      => $tags, 
                    'description' => $description
                );
                
                $this->addFilter(
                    new IDS_Filter(
                        $rule,
                        $description,
                        (array) $tags[0],
                        (int) $impact
                    )
                );
            }
            
            // write cache
            if($cache) {
                $cache->setCache($data);
            }             
        
        } else {
            throw new Exception(
                'ext/json not loaded.'
            );
        }
        
        return $this;
    }
}
/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */