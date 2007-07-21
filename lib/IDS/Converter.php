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
 * @author      christ1an <ch0012@gmail.com>
 * @author		.mario <mario.heiderich@gmail.com>
 * @version     $Id:Converter.php 174 2007-06-18 15:41:56Z mario $
 */
class IDS_Converter {

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

        # check if value matches typical charCode pattern
        if (preg_match_all('/(?:[\d+-=\/\* ]+(?:\s?,\s?[\d+-=\/\* ]+)+)/s', $value, $matches)) {
            
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
			
            $value .= ' [' . $converted . '] ';
        }

        # check for octal charcode pattern
        if (preg_match_all('/(?:(?:[\\\]+\d+\s*){2,})/iDs', $value, $matches)) {

            $converted  = '';
            $charcode   = explode('\\', preg_replace('/\s/', '', implode(',', $matches[0])));

            foreach ($charcode as $char) {
                if (!empty($char)) {
                    $converted .= chr(octdec($char));                               
                }
            }       
			
            $value .= ' [' . $converted . '] ';
        }

        # check for hexadecimal charcode pattern
        if (preg_match_all('/(?:(?:[\\\]+\w+\s*){2,})/iDs', $value, $matches)) {

            $converted  = '';
            $charcode   = explode('\\', preg_replace('/[ux]/', '', implode(',', $matches[0])));

            foreach ($charcode as $char) {
                if (!empty($char)) {
                	$converted .= chr(hexdec($char));                               
                }
            }
			
            $value .= ' [' . $converted . '] ';
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
        if (preg_match('/(?:\<!-|-->|\/\*|\*\/|\/\/\W*\w+\s*$)|(?:(?:#|--|{)\s*$)/Ds', $value)) {            

            $pattern = array('/(?:(?:<!)(?:(?:--(?:[^-]*(?:-[^-]+)*)--\s*)*)(?:>))/Ds', 
                             '/(?:(?:\/\*\/*[^\/\*]*)+\*\/)/Ds', 
                             '/(?:(?:\/\/|--|#|{).*)/Ds'
                            );
            
            $converted = preg_replace($pattern, null, $value);
    
            $value .= ' [' . $converted . '] ';    
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
        if(get_magic_quotes_gpc()){
            $compare = stripslashes($value);  
        }

        $pattern = array('/("\s*[\W]+\s*\n*")*/Ds',
                         '/(";\w\s*+=\s*\w?\s*\n*")*/Ds',
                         '/("[|&;]+\s*[^|&\n]*[|&]+\s*\n*"?)*/Ds',
                         '/(";\s*\w+\W+\w*\s*[|&]*")*/Ds', 
                         '/(?:"?\+[^"]*")/Ds'
                         ); 

        # strip out concatenations
        $converted = preg_replace($pattern, null, $compare);
            
        if($compare != $converted){    
            $value .= ' [' . $converted . '] ';  
        }
        
        return $value;    
    }
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */