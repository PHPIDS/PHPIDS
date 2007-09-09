<?php

/**
 * PHP IDS
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
 * PHPIDS specific utility class to convert charsets manually
 *
 * Note that if you make use of IDS_Converter::runAll(), existing class
 * methods will be executed in the same order as they are implemented in the
 * class tree!
 *
 * @author      christ1an <ch0012@gmail.com>
 * @author		.mario <mario.heiderich@gmail.com>
 * @version     $Id$
 */
class IDS_Converter {

	/**
	 * Runs all converter functions
	 *
	 * Note that if you make use of IDS_Converter::runAll(), existing class
	 * methods will be executed in the same order as they are implemented in the
	 * class tree!
	 *
     * @param   string  $value
	 * @return	string
	 */
	 public static function runAll($value) {
	 	$methods = get_class_methods(__CLASS__);
		
		$key = array_search('runAll', $methods);
		unset($methods[$key]);
				
		foreach ($methods as $key => $func) {
			$value = self::$func($value);
		}
		
		return $value;
	 }

    /**
     * Converts listed UTF-7 tags to UTF-8
     *
     * @param   string  $data
     * @return  string  converted $data
     */
    public static function convertFromUTF7($data) {

        $schemes = array(
            '+AFwAIg'  => '"',
            '+ADw-'     => '<',
            '+AD4-'     => '>',
            '+AFs'     => '[',
            '+AF0'     => ']',
            '+AHs'     => '{',
            '+AH0'     => '}',
            '+AFw'     => '\\',
            '+ADs'     => ';',
            '+ACM'     => '#',
            '+ACY'     => '&',
            '+ACU'     => '%',
            '+ACQ'     => '$',
            '+AD0'     => '=',
            '+AGA'     => '`',
            '+ALQ'     => '"',
            '+IBg'     => '"',
            '+IBk'     => '"',     
            '+AHw'     => '|',
            '+ACo'     => '*',
            '+AF4'     => '^'
        );
        
        $data = str_ireplace(array_keys($schemes), array_values($schemes), $data);  

        return $data;
    }

    /**
     * Checks for common charcode pattern and decodes them
     * 
     * @return  string  $value
     */ 
     public static function convertFromJSCharcode($value) {   

        $matches = array();
     	
        // check if value matches typical charCode pattern
        if (preg_match_all('/(?:[\d+-=\/\* ]+(?:\s?,\s?[\d+-=\/\* ]+)+){2,}/ms', $value, $matches)) {
            
            $converted  = '';
            $string = implode(',', $matches[0]);
            $string = preg_replace('/\s/', '', $string);
            $string = preg_replace('/\w+=/', '', $string);
            $charcode = explode(',', $string);       
            
            foreach ($charcode as $char) {
                $char = preg_replace('/[\W]0/s', '', $char);
                if (preg_match_all('/\d*[+-\/\* ]\d+/', $char, $matches)) {
                    $match = preg_split('/([\W]?\d+)/', (implode('', $matches[0])), NULL, PREG_SPLIT_DELIM_CAPTURE); 
                    $converted .= chr(array_sum($match));
    
                } elseif(!empty($char)) {
                    $converted .= chr($char);                               
                }                              
            }
			
            $value .= "\n[" . $converted . "] ";
        }

        # check for octal charcode pattern
        if (preg_match_all('/(?:(?:[\\\]+\d+\s*){2,})/ims', $value, $matches)) {

            $converted  = '';
            $charcode   = explode('\\', preg_replace('/\s/', '', implode(',', $matches[0])));

            foreach ($charcode as $char) {
                if (!empty($char)) {
                    $converted .= chr(octdec($char));                               
                }
            }       
			
            $value .= "\n[" . $converted . "] ";
        }

        # check for hexadecimal charcode pattern
        if (preg_match_all('/(?:(?:[\\\]+\w+\s*){2,})/ims', $value, $matches)) {

            $converted  = '';
            $charcode   = explode('\\', preg_replace('/[ux]/', '', implode(',', $matches[0])));

            foreach ($charcode as $char) {
                if (!empty($char)) {
                	$converted .= chr(hexdec($char));                               
                }
            }
			
            $value .= "\n[" . $converted . "] ";
        }

        return $value;
     }

    /**
     * Check for comments and erases them if available
     * 
     * @return  string  $value
     */ 
    public static function convertFromCommented($value) {

        # check for existing comments
        if (preg_match('/(?:\<!-|-->|\/\*|\*\/|\/\/\W*\w+\s*$)|(?:(?:#|--|{)\s*$)/ms', $value)) {            

            $pattern = array('/(?:(?:<!)(?:(?:--(?:[^-]*(?:-[^-]+)*)--\s*)*)(?:>))/ms', 
                             '/(?:(?:\/\*\/*[^\/\*]*)+\*\/)/ms', 
                             '/(?:(?:\/\/|--|#|{).*)/ms'
                            );
            
            $converted = preg_replace($pattern, null, $value);
            
            $value .= "\n[" . $converted . "] ";
        } 
          
        return $value;
    }

    /**
     * Normalize quotes
     * 
     * @return  string  $value
     */ 
    public static function convertQuotes($value) {

        # normalize different quotes to "
        $pattern = array('\'', '`', '´', '’', '‘');
        
        $value = str_replace($pattern, '"', $value);
          
        return $value;
    }     

    /**
     * Converts basic concatenations
     * 
     * @return  string  $value
     */ 
    public static function convertConcatenations($value) {

        $compare = '';
        if (get_magic_quotes_gpc()) {
            $compare = stripslashes($value);  
        }

        $pattern = array('/(?:"\s*;.{8,24}:\s*")/ms',
                         '/(";\w+=)|(!""&&")/ms', 
                         '/(?:"?"\+""?\+?"?)|(?:;\w+=")|(?:"[|&]{2,})/ms',
                         '/("\s*[\W]+\s*\n*")*/ms',
                         '/(";\w\s*+=\s*\w?\s*\n*")*/ms',
                         '/("[|&;]+\s*[^|&\n]*[|&]+\s*\n*"?)*/ms',
                         '/(";\s*\w+\W+\w*\s*[|&]*")*/ms', 
                         '/(?:"?\+[^"]*")/ms' 
                         ); 

        # strip out concatenations
        $converted = preg_replace($pattern, null, $compare);
            
        if ($compare != $converted) {    
            $value .= "\n[" . $converted . "] ";
        }

        return $value;    
    }
    
    /**
     * Converts from hex/dec entities
     * 
     * @return  string  $value
     */     
    public static function convertEntities($value) {
    
        $converted = '';
        if(preg_match('/&#x?[\w]+;/ms', $value)){
            $converted = html_entity_decode($value);   
            $value .= "\n[" . $converted . "] ";     
        }
        
        return $value;  
    }    
    
    /**
     * Detects nullbytes and controls chars via ord()
     * 
     * @return  string  $value
     */
    public static function convertFromControlChars($value) {

    	#critical ctrl values
    	$crlf = array(0,1,2,3,4,5,6,7,8,11,12,14,15,16,17,18,19);

    	$values = str_split($value);
    	foreach($values  as $item) {
    		if(in_array(ord($item), $crlf, true)) {
                $value .= "\n[ %00 ] ";
                return $value;
    		}
    	}
    	return $value;
    }

    /**
     * Basic approach to fight attacks using common parser bugs
     * 
     * @return  string  $value
     */
    public static function convertParserBugs($value) {

    	$search = array('\a', '\l');
    	$replace = array('a', 'l');
    	
    	$value = str_replace($search, $replace, $value);
    	
        return $value;
    }    

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */