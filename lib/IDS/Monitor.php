<?php

/**
 * PHPIDS
 *
 * Requirements: PHP5, SimpleXML
 *
 * Copyright (c) 2008 PHPIDS group (https://phpids.org)
 *
 * PHPIDS is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, version 3 of the License, or
 * (at your option) any later version.
 *
 * PHPIDS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with PHPIDS. If not, see <http://www.gnu.org/licenses/>.
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
 * Monitoring engine
 *
 * This class represents the core of the frameworks attack detection mechanism
 * and provides functions to scan incoming data for malicious appearing script
 * fragments.
 *
 * @category  Security
 * @package   PHPIDS
 * @author    Christian Matthies <ch0012@gmail.com>
 * @author    Mario Heiderich <mario.heiderich@gmail.com>
 * @author    Lars Strojny <lars@strojny.net>
 * @copyright 2007-2009 The PHPIDS Group
 * @license   http://www.gnu.org/licenses/lgpl.html LGPL
 * @link      http://php-ids.org/
 */

namespace IDS;

use IDS\Filter\Storage;

class Monitor
{

    /**
     * Tags to define what to search for
     *
     * Accepted values are xss, csrf, sqli, dt, id, lfi, rfe, spam, dos
     *
     * @var array
     */
    private $tags = null;

    /**
     * Request array
     *
     * Array containing raw data to search in
     *
     * @var array
     */
    private $request = null;

    /**
     * Container for filter rules
     *
     * Holds an instance of IDS_Filter_Storage
     *
     * @var object
     */
    private $storage = null;

    /**
     * Results
     *
     * Holds an instance of IDS_Report which itself provides an API to
     * access the detected results
     *
     * @var object
     */
    private $report = null;

    /**
     * Scan keys switch
     *
     * Enabling this property will cause the monitor to scan both the key and
     * the value of variables
     *
     * @var boolean
     */
    public $scanKeys = false;

    /**
     * Exception container
     *
     * Using this array it is possible to define variables that must not be
     * scanned. Per default, utmz google analytics parameters are permitted.
     *
     * @var array
     */
    private $exceptions = array();

    /**
     * Html container
     *
     * Using this array it is possible to define variables that legally
     * contain html and have to be prepared before hitting the rules to
     * avoid too many false alerts
     *
     * @var array
     */
    private $html = array();

    /**
     * JSON container
     *
     * Using this array it is possible to define variables that contain
     * JSON data - and should be treated as such
     *
     * @var array
     */
    private $json = array();

    /**
     * Holds HTMLPurifier object
     *
     * @var object
     */
    private $htmlpurifier = null;

    /**
     * Path to HTMLPurifier source
     *
     * This path might be changed in case one wishes to make use of a
     * different HTMLPurifier source file e.g. if already used in the
     * application PHPIDS is protecting
     *
     * @var string
     */
    private $pathToHTMLPurifier = '';

    /**
     * HTMLPurifier cache directory
     *
     * @var string
     */
    private $HTMLPurifierCache = '';

    /**
     * This property holds the tmp JSON string from the
     * _jsonDecodeValues() callback
     *
     * @var string
     */
    private $tmpJsonString = '';

    /**
     * Constructor
     *
     * @param array  $request array to scan
     * @param object $init    instance of IDS_Init
     * @param array  $tags    list of tags to which filters should be applied
     * @throws \Exception     When PHP version is less than what the library supports
     *
     * @return void
     */
    public function __construct(array $request, Init $init, array $tags = null)
    {
        $version = isset($init->config['General']['min_php_version'])
            ? $init->config['General']['min_php_version'] : '5.1.6';

        if (version_compare(PHP_VERSION, $version, '<')) {
            throw new \Exception(
                'PHP version has to be equal or higher than ' . $version . ' or
                PHP version couldn\'t be determined'
            );
        }

        if (!empty($request)) {
            $this->storage = new Storage($init);
            $this->request = $request;
            $this->tags    = $tags;

            $this->scanKeys   = $init->config['General']['scan_keys'];

            $this->exceptions = isset($init->config['General']['exceptions'])
                ? $init->config['General']['exceptions'] : false;

            $this->html       = isset($init->config['General']['html'])
                ? $init->config['General']['html'] : false;

            $this->json       = isset($init->config['General']['json'])
                ? $init->config['General']['json'] : false;

            if (isset($init->config['General']['HTML_Purifier_Path'])
                && isset($init->config['General']['HTML_Purifier_Cache'])) {

                $this->pathToHTMLPurifier =
                    $init->config['General']['HTML_Purifier_Path'];

                $this->HTMLPurifierCache  = $init->getBasePath()
                    . $init->config['General']['HTML_Purifier_Cache'];
            }
        }

        $tmp_path = $init->getBasePath() . $init->config['General']['tmp_path'];

        if (!is_writeable($tmp_path)) {
            throw new \Exception(
                'Please make sure the ' .
                htmlspecialchars($tmp_path, ENT_QUOTES, 'UTF-8') .
                ' folder is writable'
            );
        }

        $this->report = new Report();
    }

    /**
     * Starts the scan mechanism
     *
     * @return object IDS_Report
     */
    public function run()
    {
        if (!empty($this->request)) {
            foreach ($this->request as $key => $value) {
                $this->iterate($key, $value);
            }
        }

        return $this->getReport();
    }

    /**
     * Iterates through given data and delegates it to IDS_Monitor::_detect() in
     * order to check for malicious appearing fragments
     *
     * @param mixed $key   the former array key
     * @param mixed $value the former array value
     *
     * @return void
     */
    private function iterate($key, $value)
    {
        if (!is_array($value)) {
            if (is_string($value)) {
                $filter = $this->detect($key, $value);

                if ($filter !== false) {
                    $this->report->addEvent(
                        new Event(
                            $key,
                            $value,
                            $filter
                        )
                    );
                }
            }
        } else {
            foreach ($value as $subKey => $subValue) {
                $this->iterate($key . '.' . $subKey, $subValue);
            }
        }
    }

    /**
     * Checks whether given value matches any of the supplied filter patterns
     *
     * @param mixed $key   the key of the value to scan
     * @param mixed $value the value to scan
     *
     * @return bool|array false or array of filter(s) that matched the value
     */
    private function detect($key, $value)
    {
        // define the pre-filter
        $prefilter = '/[^\w\s\/@!?\.]+|(?:\.\/)|(?:@@\w+)'
            . '|(?:\+ADw)|(?:union\s+select)/i';

        // to increase performance, only start detection if value
        // isn't alphanumeric
        if (!$this->scanKeys
            && (!$value || !preg_match($prefilter, $value))) {
            return false;
        } elseif ($this->scanKeys) {
            if ((!$key || !preg_match($prefilter, $key))
                && (!$value || !preg_match($prefilter, $value))) {
                return false;
            }
        }

        // check if this field is part of the exceptions
        if (is_array($this->exceptions)) {
            foreach ($this->exceptions as $exception) {
                $matches = array();
                if (preg_match('/(\/.*\/[^eE]*)$/', $exception, $matches)) {
                    if (isset($matches[1]) && preg_match($matches[1], $key)) {
                        return false;
                    }
                } else {
                    if ($exception === $key) {
                        return false;
                    }
                }
            }
        }

        // check for magic quotes and remove them if necessary
        if (function_exists('get_magic_quotes_gpc')
            && get_magic_quotes_gpc()) {
            $value = stripslashes($value);
        }
        if (function_exists('get_magic_quotes_gpc')
            && !get_magic_quotes_gpc()
            && version_compare(PHP_VERSION, '5.3.0', '>=')) {
            $value = preg_replace('/\\\(["\'\/])/im', '$1', $value);
        }

        // if html monitoring is enabled for this field - then do it!
        if (is_array($this->html) && in_array($key, $this->html, true)) {
            list($key, $value) = $this->purifyValues($key, $value);
        }

        // check if json monitoring is enabled for this field
        if (is_array($this->json) && in_array($key, $this->json, true)) {
            list($key, $value) = $this->jsonDecodeValues($key, $value);
        }

        // use the converter
        $value = Converter::runAll($value);
        $value = Converter::runCentrifuge($value, $this);

        // scan keys if activated via config
        $key = $this->scanKeys ? Converter::runAll($key)
            : $key;
        $key = $this->scanKeys ? Converter::runCentrifuge($key, $this)
            : $key;

        $filters   = array();
        $filterSet = $this->storage->getFilterSet();
        foreach ($filterSet as $filter) {

            /*
             * in case we have a tag array specified the IDS will only
             * use those filters that are meant to detect any of the
             * defined tags
             */
            if (is_array($this->tags)) {
                if (array_intersect($this->tags, $filter->getTags())) {
                    if ($this->match($key, $value, $filter)) {
                        $filters[] = $filter;
                    }
                }
            } else {
                if ($this->match($key, $value, $filter)) {
                    $filters[] = $filter;
                }
            }
        }

        return empty($filters) ? false : $filters;
    }


    /**
     * Purifies given key and value variables using HTMLPurifier
     *
     * This function is needed whenever there is variables for which HTML
     * might be allowed like e.g. WYSIWYG post bodies. It will dectect malicious
     * code fragments and leaves harmless parts untouched.
     *
     * @param mixed $key
     * @param mixed $value
     * @since  0.5
     * @throws \Exception
     *
     * @return array
     */
    private function purifyValues($key, $value)
    {
        /*
         * Perform a pre-check if string is valid for purification
         */
        if (!$this->purifierPreCheck($key, $value)) {
            return array($key, $value);
        }

        if (!is_writeable($this->HTMLPurifierCache)) {
            throw new \Exception(
                $this->HTMLPurifierCache . ' must be writeable'
            );
        }

        if (class_exists('HTMLPurifier')) {
            $config = \HTMLPurifier_Config::createDefault();
            $config->set('Attr.EnableID', true);
            $config->set('Cache.SerializerPath', $this->HTMLPurifierCache);
            $config->set('Output.Newline', "\n");
            $this->htmlpurifier = new \HTMLPurifier($config);
        } else {
            throw new \Exception(
                'HTMLPurifier class could not be found - ' .
                'make sure the purifier files are valid and' .
                ' the path is correct'
            );
        }

        $value = preg_replace('/[\x0b-\x0c]/', ' ', $value);
        $key = preg_replace('/[\x0b-\x0c]/', ' ', $key);

        $purified_value = $this->htmlpurifier->purify($value);
        $purified_key   = $this->htmlpurifier->purify($key);

        $redux_value = strip_tags($value);
        $redux_key   = strip_tags($key);

        if ($value != $purified_value || $redux_value) {
            $value = $this->diff($value, $purified_value, $redux_value);
        } else {
            $value = null;
        }
        if ($key != $purified_key) {
            $key = $this->diff($key, $purified_key, $redux_key);
        } else {
            $key = null;
        }

        return array($key, $value);
    }

    /**
     * This method makes sure no dangerous markup can be smuggled in
     * attributes when HTML mode is switched on.
     *
     * If the precheck considers the string too dangerous for
     * purification false is being returned.
     *
     * @param mixed $key
     * @param mixed $value
     * @since  0.6
     *
     * @return boolean
     */
    private function purifierPreCheck($key = '', $value = '')
    {
        /*
         * Remove control chars before pre-check
         */
        $tmp_value = preg_replace('/\p{C}/', null, $value);
        $tmp_key = preg_replace('/\p{C}/', null, $key);

        $precheck = '/<(script|iframe|applet|object)\W/i';
        if (preg_match($precheck, $tmp_key)
            || preg_match($precheck, $tmp_value)) {
            return false;
        }

        return true;
    }

    /**
     * This method calculates the difference between the original
     * and the purified markup strings.
     *
     * @param string $original the original markup
     * @param string $purified the purified markup
     * @param string $redux    the string without html
     * @since 0.5
     *
     * @return string the difference between the strings
     */
    private function diff($original, $purified, $redux)
    {
        /*
         * deal with over-sensitive alt-attribute addition of the purifier
         * and other common html formatting problems
         */
        $purified = preg_replace('/\s+alt="[^"]*"/m', null, $purified);
        $purified = preg_replace('/=?\s*"\s*"/m', null, $purified);
        $original = preg_replace('/\s+alt="[^"]*"/m', null, $original);
        $original = preg_replace('/=?\s*"\s*"/m', null, $original);
        $original = preg_replace('/style\s*=\s*([^"])/m', 'style = "$1', $original);

        # deal with oversensitive CSS normalization
        $original = preg_replace('/(?:([\w\-]+:)+\s*([^;]+;\s*))/m', '$1$2', $original);

        # strip whitespace between tags
        $original = trim(preg_replace('/>\s*</m', '><', $original));
        $purified = trim(preg_replace('/>\s*</m', '><', $purified));

        $original = preg_replace(
            '/(=\s*(["\'`])[^>"\'`]*>[^>"\'`]*["\'`])/m',
            'alt$1',
            $original
        );

        // no purified html is left
        if (!$purified) {
            return $original;
        }

        // calculate the diff length
        $length = mb_strlen($original) - mb_strlen($purified);

        /*
         * Calculate the difference between the original html input
         * and the purified string.
         */
        $array_1 = preg_split('/(?<!^)(?!$)/u', html_entity_decode(urldecode($original)));
        $array_2 = preg_split('/(?<!^)(?!$)/u', $purified);

        // create an array containing the single character differences
        $differences = array();
        foreach ($array_1 as $key => $value) {
            if (!isset($array_2[$key]) || $value !== $array_2[$key]) {
                $differences[] = $value;
            }
        }

        // return the diff - ready to hit the converter and the rules
        if (intval($length) <= 10) {
            $diff = trim(join('', $differences));
        } else {
            $diff = mb_substr(trim(join('', $differences)), 0, strlen($original));
        }

        // clean up spaces between tag delimiters
        $diff = preg_replace('/>\s*</m', '><', $diff);

        // correct over-sensitively stripped bad html elements
        $diff = preg_replace(
            '/[^<](iframe|script|embed|object|applet|base|img|style)/m',
            '<$1',
            $diff
        );

        if (mb_strlen($diff) < 4) {
            return null;
        }

        return $diff . $redux;
    }

    /**
     * This method prepares incoming JSON data for the PHPIDS detection
     * process. It utilizes _jsonConcatContents() as callback and returns a
     * string version of the JSON data structures.
     *
     * @param mixed $key
     * @param mixed $value
     * @since  0.5.3
     *
     * @return array
     */
    private function jsonDecodeValues($key, $value)
    {
        $tmp_key   = json_decode($key);
        $tmp_value = json_decode($value);

        if ($tmp_value && is_array($tmp_value) || is_object($tmp_value)) {
            array_walk_recursive($tmp_value, array($this, 'jsonConcatContents'));
            $value = $this->tmpJsonString;
        } else {
            $this->tmpJsonString .=  " " . $tmp_value . "\n";
        }

        if ($tmp_key && is_array($tmp_key) || is_object($tmp_key)) {
            array_walk_recursive($tmp_key, array($this, 'jsonConcatContents'));
            $key = $this->tmpJsonString;
        } else {
            $this->tmpJsonString .=  " " . $tmp_key . "\n";
        }

        return array($key, $value);
    }

    /**
     * This is the callback used in _jsonDecodeValues(). The method
     * concatenates key and value and stores them in $this->tmpJsonString.
     *
     * @param mixed $key
     * @param mixed $value
     * @since  0.5.3
     *
     * @return void
     */
    private function jsonConcatContents($key, $value)
    {
        if (is_string($key) && is_string($value)) {
            $this->tmpJsonString .=  $key . " " . $value . "\n";
        } else {
            $this->jsonDecodeValues(
                json_encode($key),
                json_encode($value)
            );
        }
    }

    /**
     * Matches given value and/or key against given filter
     *
     * @param mixed  $key    the key to optionally scan
     * @param mixed  $value  the value to scan
     * @param object $filter the filter object
     *
     * @return boolean
     */
    private function match($key, $value, $filter)
    {
        if ($this->scanKeys) {
            if ($filter->match($key)) {
                return true;
            }
        }

        if ($filter->match($value)) {
            return true;
        }

        return false;
    }

    /**
     * Sets exception array
     *
     * @param mixed $exceptions the thrown exceptions
     *
     * @return void
     */
    public function setExceptions($exceptions)
    {
        if (!is_array($exceptions)) {
            $exceptions = array($exceptions);
        }

        $this->exceptions = $exceptions;
    }

    /**
     * Returns exception array
     *
     * @return array
     */
    public function getExceptions()
    {
        return $this->exceptions;
    }

    /**
     * Sets html array
     *
     * @param mixed $html the fields containing html
     * @since 0.5
     *
     * @return void
     */
    public function setHtml($html)
    {
        if (!is_array($html)) {
            $html = array($html);
        }

        $this->html = $html;
    }

    /**
     * Adds a value to the html array
     *
     * @since 0.5
     *
     * @return void
     */
    public function addHtml($value)
    {
        $this->html[] = $value;
    }

    /**
     * Returns html array
     *
     * @since 0.5
     *
     * @return array the fields that contain allowed html
     */
    public function getHtml()
    {
        return $this->html;
    }

    /**
     * Sets json array
     *
     * @param mixed $json the fields containing json
     * @since 0.5.3
     *
     * @return void
     */
    public function setJson($json)
    {
        if (!is_array($json)) {
            $json = array($json);
        }

        $this->json = $json;
    }

    /**
     * Adds a value to the json array
     *
     * @param  string the value containing JSON data
     * @since  0.5.3
     *
     * @return void
     */
    public function addJson($value)
    {
        $this->json[] = $value;
    }

    /**
     * Returns json array
     *
     * @since 0.5.3
     *
     * @return array the fields that contain json
     */
    public function getJson()
    {
        return $this->json;
    }

    /**
     * Returns storage container
     *
     * @return array
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * Returns report object providing various functions to work with
     * detected results. Also the centrifuge data is being set as property
     * of the report object.
     *
     * @return object IDS_Report
     */
    public function getReport()
    {
        if (isset($this->centrifuge) && $this->centrifuge) {
            $this->report->setCentrifuge($this->centrifuge);
        }

        return $this->report;
    }
}

/**
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: sw=4 ts=4 expandtab
 */
