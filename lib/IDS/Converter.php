<?php

/**
 * PHP IDS
 *
 * Requirements: PHP5, SimpleXML, MultiByte Extension (optional)
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
 * @author		christ1an <ch0012@gmail.com>
 * @version		$Id$
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
            '+AFwAIg'   => '"',
            '+AFw\''    => '\'',
            '+ADw'      => '<',
            '+AD4'      => '>',
            '+AFs'      => '[',
            '+AF0'      => ']',
            '+AHs'      => '{',
            '+AH0'      => '}',
            '+AFwAXA'   => '\\',
            '+ADs'      => ';',
            '+ACM'      => '#',
            '+ACY'      => '&',
            '+ACU'      => '%',
            '+ACQ'      => '$',
            '+AD0'      => '=',
            '+AGA'      => '`',
            '+ALQ'      => '�',
			'+IBg-'		=> '�',
			'+IBk-'		=> '�',		
            '+AHw-'     => '|',
            '+ACo-'     => '*',
            '+AF4-'     => '^'
        );
        
        foreach ($schemes as $scheme => $replacement) {         
            $data = str_replace($scheme, $replacement, $data);  
        }

        return $data;
    }

    /**
     * Converts urlencoded special chars to double-urlencoded 
     * chars to enable detection
     *
     * @param   string  $data
     * @return  string  converted $data
     */
    public static function convertFromURLNullByte($data) {

        $schemes = array(
            '%0'   => '%250', 
            '%1'   => '%251'   
        );
        
        foreach ($schemes as $scheme => $replacement) {         
            $data = str_replace($scheme, $replacement, $data);  
        }

        return $data;        
    }
}
