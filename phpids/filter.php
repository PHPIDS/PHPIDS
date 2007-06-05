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
 * Abstract filter class
 *
 * A basic implementation of a filter object
 *
 * @author	Lars Strojny <lstrojny@neu.de>
 */
abstract class IDS_Filter_Abstract {

/**
 * Filter rule
 *
 * @var	mixed
 */
protected $_rule;

/**
 * List of tags of the filter
 *
 * @var	array
 */
protected $_tags = array();

/**
 * Filter impact level
 *
 * @var	integer
 */
protected $_impact = 0;

/**
 * Constructor
 *
 * @param	mixed $rule				Filter rule
 * @param	string $description		Filter description
 * @param	array $tags				List of tags
 * @param	integer $impact			Filter impact level
 */
public function __construct($rule, $description, Array $tags, $impact) {
	$this->_description = $description;
	$this->_rule = $rule;
	$this->_tags = $tags;
	$this->_impact = $impact;
}

/**
 * Abstract match method
 *
 * The concrete match process which returns a boolean to inform
 * about a match
 *
 * @return	bool
 */
abstract public function match($string);

/**
 * Get filter description
 *
 * @return	string	Filter description
 */
public function getDescription() {
	return $this->_description;
}

/**
 * Return list of tags
 *
 * @return	array	List of tags
 */
public function getTags() {
	return $this->_tags;
}

/**
 * Return filter rule
 *
 * @return	mixed	Filter rule
 */
public function getRule() {
	return $this->_rule;
}

/**
 * Get filter impact level
 *
 * @return	integer	Impact level
 */
public function getImpact() {
	return $this->_impact;
}
}

/**
 * Regexp filter class
 *
 * The filter class based on regular expression matching is the default
 * filter class used in PHP IDS.
 *
 * @author Lars Strojny <lstrojny@neu.de>
 */
class IDS_Filter_Regexp extends IDS_Filter_Abstract {

/**
 * Match method
 *
 * IDS_Filter_Regexp->match() used preg_match() to match the rule against
 * the given string.
 *
 * @return	bool Filter matched?
 */
public function match($string) {
	if (!is_string($string)) {
		throw new Exception('
			Invalid argument. Exptected a string, got ' . gettype($string)
		);
	}
	
	return (bool) preg_match('/' . $this->getRule() . '/', $string);
}
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */
