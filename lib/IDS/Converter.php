<?php

/**
 * PHPIDS
 * 
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
 * PHP version 5.1.6+
 * 
 * @category Security
 * @package  PHPIDS
 * @author   Mario Heiderich <mario.heiderich@gmail.com>
 * @author   Christian Matthies <ch0012@gmail.com>
 * @author   Lars Strojny <lars@strojny.net>
 * @license  http://www.gnu.org/licenses/lgpl.html LGPL
 * @link     http://php-ids.org/
 */

/**
 * PHPIDS specific utility class to convert charsets manually
 *
 * Note that if you make use of IDS_Converter::runAll(), existing class
 * methods will be executed in the same order as they are implemented in the
 * class tree!
 *
 * @category  Security
 * @package   PHPIDS
 * @author    Christian Matthies <ch0012@gmail.com>
 * @author    Mario Heiderich <mario.heiderich@gmail.com>
 * @author    Lars Strojny <lars@strojny.net>
 * @copyright 2007 The PHPIDS Group
 * @license   http://www.gnu.org/licenses/lgpl.html LGPL
 * @version   Release: $Id:Converter.php 517 2007-09-15 15:04:13Z mario $
 * @link      http://php-ids.org/
 */
class IDS_Converter
{
    /**
     * Runs all converter functions
     *
     * Note that if you make use of IDS_Converter::runAll(), existing class
     * methods will be executed in the same order as they are implemented in the
     * class tree!
     *
     * @param string $value the value to convert
     * 
     * @static
     * @return string
     */
    public static function runAll($value) 
    {
        $methods = get_class_methods(__CLASS__);

        $key = array_search('runAll', $methods);
        unset($methods[$key]);

        foreach ($methods as $key => $func) {
            $value = self::$func($value);
        }

        return $value;
    }

    /**
     * Check for comments and erases them if available
     *
     * @param string $value the value to convert
     * 
     * @static
     * @return string
     */
    public static function convertFromCommented($value) 
    {
        // check for existing comments
        if (preg_match('/(?:\<!-|-->|\/\*|\*\/|\/\/\W*\w+\s*$)|' . 
            '(?:--[^-]*-)/ms', $value)) {

            $pattern = array(
                '/(?:(?:<!)(?:(?:--(?:[^-]*(?:-[^-]+)*)--\s*)*)(?:>))/ms',
                '/(?:(?:\/\*\/*[^\/\*]*)+\*\/)/ms',
                '/(?:--[^-]*-)/ms'
            );

            $converted = preg_replace($pattern, ';', $value);
            $value .= "\n" . $converted;
        }
        //make sure inline comments are detected and converted correctly
        $value = preg_replace('/(<\w+)\/+(\w+=?)/m', '$1/$2', $value);
        $value = preg_replace('/[^\\\:]\/\/(.*)$/m', '/**/$1', $value);
        
        return $value;
    }    
    
    /**
     * Strip newlines
     *
     * @param string $value the value to convert
     * 
     * @static
     * @return string
     */
    public static function convertFromNewLines($value) 
    {
        //check for inline linebreaks
        $search = array('\r', '\n', '\f', '\t');
        $value = str_replace($search, ';', $value);
        
        //convert real linebreaks
        return preg_replace('/(?:\n|\r)/m', ' ', $value);
    }

    /**
     * Checks for common charcode pattern and decodes them
     *
     * @param string $value the value to convert
     * 
     * @static
     * @return string
     */
    public static function convertFromJSCharcode($value) 
    {
        $matches = array();

        // check if value matches typical charCode pattern
        if (preg_match_all('/(?:[\d+-=\/\* ]+(?:\s?,\s?[\d+-=\/\* ]+)+){4,}/ms', 
            $value, $matches)) {

            $converted = '';
            $string    = implode(',', $matches[0]);
            $string    = preg_replace('/\s/', '', $string);
            $string    = preg_replace('/\w+=/', '', $string);
            $charcode  = explode(',', $string);

            foreach ($charcode as $char) {
                $char = preg_replace('/\W0/s', '', $char);

                if (preg_match_all('/\d*[+-\/\* ]\d+/', $char, $matches)) {
                    $match = preg_split('/(\W?\d+)/',
                                        (implode('', $matches[0])),
                                        null,
                                        PREG_SPLIT_DELIM_CAPTURE);

                    if (array_sum($match) >= 20 && array_sum($match) <= 127) {
                        $converted .= chr(array_sum($match));
                    }

                } elseif (!empty($char) && $char >= 20 && $char <= 127) {
                    $converted .= chr($char);
                }
            }

            $value .= "\n" . $converted;
        }

        // check for octal charcode pattern
        if (preg_match_all('/(?:(?:[\\\]+\d+\s*){8,})/ims', $value, $matches)) {

            $converted = '';
            $charcode  = explode('\\', preg_replace('/\s/', '', implode(',', 
                $matches[0])));

            foreach ($charcode as $char) {
                if (!empty($char)) {
                    if (octdec($char) >= 20 && octdec($char) <= 127) {
                        $converted .= chr(octdec($char));
                    }
                }
            }
            $value .= "\n" . $converted;
        }

        // check for hexadecimal charcode pattern
        if (preg_match_all('/(?:(?:[\\\]+\w+\s*){8,})/ims', $value, $matches)) {

            $converted = '';
            $charcode  = explode('\\', preg_replace('/[ux]/', '', implode(',', 
                $matches[0])));

            foreach ($charcode as $char) {
                if (!empty($char)) {
                    if (hexdec($char) >= 20 && hexdec($char) <= 127) {
                        $converted .= chr(hexdec($char));
                    }
                }
            }
            $value .= "\n" . $converted;
        }

        return $value;
    }

    /**
     * Eliminate JS regex modifiers
     *
     * @param string $value the value to convert
     * 
     * @static
     * @return string
     */
    public static function convertJSRegexModifiers($value) 
    {
        $value   = preg_replace('/\/[gim]/', '/', $value);

        return $value;
    }    
    
    /**
     * Normalize quotes
     *
     * @param string $value the value to convert
     * 
     * @static
     * @return string
     */
    public static function convertQuotes($value) 
    {
        // normalize different quotes to "
        $pattern = array('\'', '`', '´', '’', '‘', '&quot', '&apos');
        $value   = str_replace($pattern, '"', $value);

        return $value;
    }

    /**
     * Converts basic SQL keywords and obfuscations
     *
     * @param string $value the value to convert
     * 
     * @static
     * @return string
     */
    public static function convertFromSQLKeywords($value) 
    {
        $pattern = array('/(?:IS\s+null)|(LIKE\s+null)|' . 
            '(?:IN[+\s]*\([^()]+\))/ims');
        $value   = preg_replace($pattern, '=0', $value);

        $pattern = array('/[^\w,]NULL|\\\N|TRUE|FALSE|UTC_TIME|' . 
                         'LOCALTIME(?:STAMP)?|CURRENT_\w+|BINARY|' . 
                         '(?:(?:ASCII|SOUNDEX|' . 
                         'MD5|R?LIKE)[+\s]*\([^()]+\))/ims');
        $value   = preg_replace($pattern, 0, $value);

        $pattern = array('/(?:NOT\s+BETWEEN)|(?:IS\s+NOT)|(?:NOT\s+IN)|' . 
                         '(?:XOR|DIV|NOT\W|<>|RLIKE(?:\s+BINARY)?)|' . 
                         '(?:REGEXP\s+BINARY)|' . 
                         '(?:SOUNDS\s+LIKE)/ims');
        $value   = preg_replace($pattern, '=', $value);

        return $value;
    }

    /**
     * Converts basic concatenations
     *
     * @param string $value the value to convert
     * 
     * @static
     * @return string
     */
    public static function convertConcatenations($value) 
    {
        //normalize remaining backslashes
        if ($value != preg_replace('/(\w)\\\/', "$1", $value)) {
            $value .= preg_replace('/(\w)\\\/', "$1", $value);
        }       
        
        $compare = stripslashes($value);

        $pattern = array('/(?:<\/\w+>\+<\w+>)/s',
            '/(?:":\d+[^"[]+")/s',
            '/(?:"?"\+\w+\+")/s',
            '/(?:"\s*;[^"]+")|(?:";[^"]+:\s*")/s',
            '/(?:"\s*(?:;|\+).{8,18}:\s*")/s',
            '/(?:";\w+=)|(?:!""&&")|(?:~)/s',
            '/(?:"?"\+""?\+?"?)|(?:;\w+=")|(?:"[|&]{2,})/s',
            '/(?:"\s*\W+")/s',
            '/(?:";\w\s*+=\s*\w?\s*")/s',
            '/(?:"[|&;]+\s*[^|&\n]*[|&]+\s*"?)/s',
            '/(?:";\s*\w+\W+\w*\s*[|&]*")/s',
            '/(?:"\s*"\s*\.)/s');

        // strip out concatenations
        $converted = preg_replace($pattern, null, $compare);
        
        //strip object traversal
        $converted = preg_replace('/\w(\.\w\()/', "$1", $converted);
        

        //convert JS special numbers
        $converted = preg_replace('/(?:\(*[.\d]e[+-]*\d+\)*)|(?:NaN|Infinity)/ims', 1, $converted);
        
        if ($compare != $converted) {
            $value .= "\n" . $converted;
        }
        
        return $value;
    }

    /**
     * Converts from hex/dec entities
     *
     * @param string $value the value to convert
     * 
     * @static
     * @return string
     */
    public static function convertEntities($value) 
    {
        $converted = null;
        if (preg_match('/&#x?[\w]+/ms', $value)) {
            $converted = preg_replace('/(&#x?[\w]{2}\d?);?/ms', '$1;', $value);
            $converted = html_entity_decode($converted);
            $value    .= "\n" . str_replace(';', null, $converted);
        }

        return $value;
    }

    /**
     * Detects nullbytes and controls chars via ord()
     *
     * @param string $value the value to convert
     * 
     * @static
     * @return string
     */
    public static function convertFromControlChars($value) 
    {
        // critical ctrl values
        $search = array(chr(0), chr(1), chr(2),
                        chr(3), chr(4), chr(5),
                        chr(6), chr(7), chr(8),
                        chr(11), chr(12), chr(14),
                        chr(15), chr(16), chr(17),
                        chr(18), chr(19));
        $value  = str_replace($search, '%00', $value);
        
        //take care for malicious unicode characters
        if (preg_match('/(?:%E(?:2|3)%8(?:0|1)%(?:A|8|9)\w|%EF%BB%BF)|' . 
            '(?:&#(?:65|8)\d{3};?)/i', urlencode($value))) {
            return urldecode(preg_replace('/(?:%E(?:2|3)%8(?:0|1)%(?:A|8|9)' . 
                '\w|%EF%BB%BF)|(?:&#(?:65|8)\d{3};?)/i', null, 
                    urlencode($value))) . "\n%00";
        }

        if (preg_match('/(?:&[#x]*(200|820|[jlmnrwz]+)\w?;?)/i', $value)) {
            return urldecode(
                preg_replace('/(?:&[#x]*(200|820|[jlmnrwz]+)\w?;?)/i', null, 
                    $value)) . "\n%00";
        }        
        
        if (preg_match('/(?:&#(?:65|8)\d{3};?)|(?:&#x(?:fe|20)\w{2};?)/i', 
            $value)) {
            return urldecode(preg_replace('/(?:&#(?:65|8)\d{3};?)|' . 
                '(?:&#x(?:fe|20)\w{2};?)/i', null, $value)) . "\n%00";
        }        
        
        return $value;
    }

    /**
     * Detects nullbytes and controls chars via ord()
     *
     * @param string $value the value to convert
     * 
     * @static
     * @return string
     */
    public static function convertFromOutOfRangeChars($value) 
    {
        $values = str_split($value);
        foreach ($values as $item) {
            if (ord($item) >= 127) {
                $value = str_replace($item, 'U', $value);
            }
        }
        
        return $value;
    }

    /**
     * Strip XML patterns
     *
     * @param string $value the value to convert
     * 
     * @static
     * @return string
     */
    public static function convertFromXML($value)
    {
        $converted = strip_tags($value);

        if ($converted != $value) {
            return $value . "\n" . $converted;
        }
        return $value;
    }

    /**
     * This method converts JS unicode code points to 
     * regular characters
     * 
     * @param string $value the value to convert
     * 
     * @static
     * @return string
     */
    public static function convertFromJSUnicode($value)
    {
        $matches = array();
        
        preg_match_all('/\\\u[0-9a-f]{4}/ims', $value, $matches);

        if(!empty($matches[0])) {
            foreach ($matches[0] as $match) {
                $value = str_replace($match, chr(hexdec(substr($match, 2, 4))), $value);
            }
            $value .= "\n\u0001";
        }
        
        return $value;
    }    

    /**
     * This method matches and translates base64 strings and fragments 
     * used in data URIs
     *
     * @param string $value the value to convert
     * 
     * @static
     * @return string
     */
    public static function convertFromNestedBase64($value) 
    {
    	$matches = array();
    	preg_match_all('/(?:^|,)\s*([a-z0-9]{30,}=*)(?:\W|$)/im', $value, $matches);
    	foreach($matches as $match) {
    		foreach($match as $item) {
	            if(isset($item)) {
	                $value .= base64_decode($item);
	            }
    		}
        }
    	return $value;
    }

    /**
     * Converts relevant UTF-7 tags to UTF-8
     *
     * @param string $value the value to convert
     * 
     * @static
     * @return string
     */
    public static function convertFromUTF7($value) 
    {
    	if(function_exists('mb_convert_encoding') 
    	   && preg_match('/\+\Aw{2,}-/m', $value)) {
            $value .= "\n" . mb_convert_encoding($value, 'UTF-8', 'UTF-7');	
    	} else {
	        //list of all critical UTF7 codepoints
	        $schemes = array(
	            '+ACI-'      => '"',
	            '+ADw-'      => '<',
	            '+AD4-'      => '>',
	            '+AFs-'      => '[',
	            '+AF0-'      => ']',
	            '+AHs-'      => '{',
	            '+AH0-'      => '}',
	            '+AFw-'      => '\\',
	            '+ADs-'      => ';',
	            '+ACM-'      => '#',
	            '+ACY-'      => '&',
	            '+ACU-'      => '%',
	            '+ACQ-'      => '$',
	            '+AD0-'      => '=',
	            '+AGA-'      => '`',
	            '+ALQ-'      => '"',
	            '+IBg-'      => '"',
	            '+IBk-'      => '"',
	            '+AHw-'      => '|',
	            '+ACo-'      => '*',
	            '+AF4-'      => '^',
	            '+ACIAPg-'   => '">',
	            '+ACIAPgA8-' => '">'
	        );
	        $value = str_ireplace(array_keys($schemes), 
	            array_values($schemes), $value);    		
    	}
        return $value;
    }    
    
    /**
     * This method is the centrifuge prototype
     *
     * @param string $value the value to convert
     * 
     * @static
     * @return string
     */
    public static function convertFromCentrifuge($value) 
    {
        if (strlen($value) > 25) {
            // Check for the attack char ratio

            $stripped_length = strlen(
                preg_replace('/[\w\s\p{L}.,\/]*/ms', null, $value));
            $overall_length  = strlen(
                preg_replace('/\w{3,}/', '123', 
                    preg_replace('/\s{2,}/ms', null, $value)));

            if($stripped_length != 0 && $overall_length/$stripped_length <= 3.5) {
                $value .= "\n$[!!!]";
            }
        }

        
        if (strlen($value) > 40) {
            // Replace all non-special chars
            $converted =  preg_replace('/[\w\s\p{L}]/', null, $value);

            // Split string into an array, unify and sort
            $array = str_split($converted);
            $array = array_unique($array);
            asort($array);

            // Normalize certain tokens
            $schemes = array(
                '~' => '+',
                '^' => '+',
                '|' => '+',
                '*' => '+',
                '%' => '+',
                '&' => '+',
                '/' => '+'
            );

            $converted = implode($array);
            $converted = str_replace(array_keys($schemes), 
                array_values($schemes), $converted);
            $converted = preg_replace('/[+-]\s*\d+/', '+', $converted);    
            $converted = preg_replace('/[()[\]{}]/', '(', $converted);
            $converted = preg_replace('/[!?,.:=]/', ':', $converted);
            $converted = preg_replace('/[^:(+]/', null, stripslashes($converted));

            
            
            // Sort again and implode
            $array = str_split($converted);
            asort($array);
            $converted = implode($array);

            if (preg_match('/(?:\({2,}\+{2,}:{2,})|(?:\({2,}\+{2,}:+)|' . 
                '(?:\({3,}\++:{2,})/', $converted)) {
                return $value . "\n" . $converted;
            }
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