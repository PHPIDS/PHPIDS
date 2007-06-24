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
            '+AFw\''   => '\'',
            '+ADw-'     => '<',
            '+AD4-'     => '>',
            '+AFs'     => '[',
            '+AF0'     => ']',
            '+AHs'     => '{',
            '+AH0'     => '}',
            '+AFwAXA'  => '\\',
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
        if (preg_match_all('/(?:\d*(?:\s?,\s?\d+)+)/iDs', $value, $matches)) {

            $converted  = '';
            $charcode   = explode(',', preg_replace('/\s/', '', implode(',', $matches[0])));       
            foreach($charcode as $char){
                if(!empty($char)){
                    $converted .= chr($char);                               
                }                              
            }
            $value .= ' [' . $converted . '] ';
        }

        # check for octal charcode pattern
        if (preg_match_all('/(?:(?:[\\\]+\d+\s*){2,})/iDs', $value, $matches)) {

            $converted  = '';
            $charcode   = explode('\\', preg_replace('/\s/', '', implode(',', $matches[0])));

            foreach($charcode as $char){
                if(!empty($char)){
                    $converted .= chr(octdec($char));                               
                }
            }       
            $value .= ' [' . $converted . '] ';
        }

        # check for hexadecimal charcode pattern
        if (preg_match_all('/(?:(?:[\\\]+\w+\s*){2,})/iDs', $value, $matches)) {

            $converted  = '';
            $charcode   = explode('\\', preg_replace('/[ux]/', '', implode(',', $matches[0])));

            foreach($charcode as $char){
                if(!empty($char)){
                    $converted .= chr(hexdec($char));                               
                }
            }   
            $value .= ' [' . $converted . '] ';
        }

        return $value;
     }
}
