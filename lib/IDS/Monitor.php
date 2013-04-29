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
namespace IDS;

use IDS\Filter\Storage;

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
     * Container for filter rules
     *
     * Holds an instance of Storage
     *
     * @var Storage
     */
    private $storage = null;

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
     * @var \HTMLPurifier
     */
    private $htmlPurifier = null;

    /**
     * HTMLPurifier cache directory
     *
     * @var string
     */
    private $HTMLPurifierCache = '';

    /**
     * This property holds the tmp JSON string from the _jsonDecodeValues() callback
     *
     * @var string
     */
    private $tmpJsonString = '';

    /**
     * Constructor
     *
     * @throws \InvalidArgumentException When PHP version is less than what the library supports
     * @throws \Exception
     * @param  Init       $init instance of IDS_Init
     * @param  array|null $tags list of tags to which filters should be applied
     * @return Monitor
     */
    public function __construct(Init $init, array $tags = null)
    {
        $this->storage    = new Storage($init);
        $this->tags       = $tags;
        $this->scanKeys   = $init->config['General']['scan_keys'];
        $this->exceptions = isset($init->config['General']['exceptions']) ? $init->config['General']['exceptions'] : array();
        $this->html       = isset($init->config['General']['html'])       ? $init->config['General']['html'] : array();
        $this->json       = isset($init->config['General']['json'])       ? $init->config['General']['json'] : array();

        if (isset($init->config['General']['HTML_Purifier_Cache'])) {
            $this->HTMLPurifierCache  = $init->getBasePath() . $init->config['General']['HTML_Purifier_Cache'];
        }

        $tmpPath = $init->getBasePath() . $init->config['General']['tmp_path'];

        if (!is_writeable($tmpPath)) {
            throw new \InvalidArgumentException("Please make sure the folder '$tmpPath' is writable");
        }
    }

    /**
     * Starts the scan mechanism
     *
     * @param  array $request
     * @return Report
     */
    public function run(array $request)
    {
        $report = new Report;
        foreach ($request as $key => $value) {
            $report = $this->iterate($key, $value, $report);
        }
        return $report;
    }

    /**
     * Iterates through given data and delegates it to IDS_Monitor::_detect() in
     * order to check for malicious appearing fragments
     *
     * @param string       $key   the former array key
     * @param array|string $value the former array value
     * @param Report       $report
     * @return Report
     */
    private function iterate($key, $value, Report $report)
    {
        if (is_array($value)) {
            foreach ($value as $subKey => $subValue) {
                $this->iterate("$key.$subKey", $subValue, $report);
            }
        } elseif (is_string($value)) {
            if ($filter = $this->detect($key, $value)) {
                $report->addEvent(new Event($key, $value, $filter));
            }
        }
        return $report;
    }

    /**
     * Checks whether given value matches any of the supplied filter patterns
     *
     * @param mixed $key   the key of the value to scan
     * @param mixed $value the value to scan
     *
     * @return Filter[] array of filter(s) that matched the value
     */
    private function detect($key, $value)
    {
        // define the pre-filter
        $preFilter = '([^\w\s/@!?\.]+|(?:\./)|(?:@@\w+)|(?:\+ADw)|(?:union\s+select))i';

        // to increase performance, only start detection if value isn't alphanumeric
        if ((!$this->scanKeys || !$key || !preg_match($preFilter, $key)) && (!$value || !preg_match($preFilter, $value))) {
            return array();
        }

        // check if this field is part of the exceptions
        foreach ($this->exceptions as $exception) {
            $matches = array();
            if (($exception === $key) || preg_match('((/.*/[^eE]*)$)', $exception, $matches) && isset($matches[1]) && preg_match($matches[1], $key)) {
                return array();
            }
        }

        // check for magic quotes and remove them if necessary
        if (function_exists('get_magic_quotes_gpc') && !get_magic_quotes_gpc()) {
            $value = preg_replace('(\\\(["\'/]))im', '$1', $value);
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
        $key = $this->scanKeys ? Converter::runAll($key) : $key;
        $key = $this->scanKeys ? Converter::runCentrifuge($key, $this) : $key;

        $filterSet = $this->storage->getFilterSet();

        if ($tags = $this->tags) {
            $filterSet = array_filter(
                $filterSet,
                function (Filter $filter) use ($tags) {
                    return (bool) array_intersect($tags, $filter->getTags());
                }
            );
        }

        $scanKeys = $this->scanKeys;
        $filterSet = array_filter(
            $filterSet,
            function (Filter $filter) use ($key, $value, $scanKeys) {
                return $filter->match($value) || $scanKeys && $filter->match($key);
            }
        );

        return $filterSet;
    }


    /**
     * Purifies given key and value variables using HTMLPurifier
     *
     * This function is needed whenever there is variables for which HTML
     * might be allowed like e.g. WYSIWYG post bodies. It will detect malicious
     * code fragments and leaves harmless parts untouched.
     *
     * @param mixed $key
     * @param mixed $value
     * @since  0.5
     * @throws \Exception
     *
     * @return array tuple [key,value]
     */
    private function purifyValues($key, $value)
    {
        /*
         * Perform a pre-check if string is valid for purification
         */
        if ($this->purifierPreCheck($key, $value)) {
            if (!is_writeable($this->HTMLPurifierCache)) {
                throw new \Exception($this->HTMLPurifierCache . ' must be writeable');
            }

            /** @var $config \HTMLPurifier_Config */
            $config = \HTMLPurifier_Config::createDefault();
            $config->set('Attr.EnableID', true);
            $config->set('Cache.SerializerPath', $this->HTMLPurifierCache);
            $config->set('Output.Newline', "\n");
            $this->htmlPurifier = new \HTMLPurifier($config);

            $value = preg_replace('([\x0b-\x0c])', ' ', $value);
            $key = preg_replace('([\x0b-\x0c])', ' ', $key);

            $purifiedValue = $this->htmlPurifier->purify($value);
            $purifiedKey   = $this->htmlPurifier->purify($key);

            $plainValue = strip_tags($value);
            $plainKey   = strip_tags($key);

            $value = $value != $purifiedValue || $plainValue ? $this->diff($value, $purifiedValue, $plainValue) : null;
            $key = $key != $purifiedKey ? $this->diff($key, $purifiedKey, $plainKey) : null;
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
     * @param string $key
     * @param string $value
     * @since  0.6
     *
     * @return boolean
     */
    private function purifierPreCheck($key = '', $value = '')
    {
        /*
         * Remove control chars before pre-check
         */
        $tmpValue = preg_replace('/\p{C}/', null, $value);
        $tmpKey = preg_replace('/\p{C}/', null, $key);

        $preCheck = '/<(script|iframe|applet|object)\W/i';
        return !(preg_match($preCheck, $tmpKey) || preg_match($preCheck, $tmpValue));
    }

    /**
     * This method calculates the difference between the original
     * and the purified markup strings.
     *
     * @param string $original the original markup
     * @param string $purified the purified markup
     * @param string $plain    the string without html
     * @since 0.5
     *
     * @return string the difference between the strings
     */
    private function diff($original, $purified, $plain)
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

        $original = preg_replace('/(=\s*(["\'`])[^>"\'`]*>[^>"\'`]*["\'`])/m', 'alt$1', $original);

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
        $array1 = preg_split('/(?<!^)(?!$)/u', html_entity_decode(urldecode($original)));
        $array2 = preg_split('/(?<!^)(?!$)/u', $purified);

        // create an array containing the single character differences
        $differences = array_diff_assoc($array1, $array2);

        // return the diff - ready to hit the converter and the rules
        $differences = trim(implode('', $differences));
        $diff = $length <= 10 ? $differences : mb_substr($differences, 0, strlen($original));

        // clean up spaces between tag delimiters
        $diff = preg_replace('/>\s*</m', '><', $diff);

        // correct over-sensitively stripped bad html elements
        $diff = preg_replace('/[^<](iframe|script|embed|object|applet|base|img|style)/m', '<$1', $diff );

        return mb_strlen($diff) >= 4 ? $diff . $plain : null;
    }

    /**
     * This method prepares incoming JSON data for the PHPIDS detection
     * process. It utilizes _jsonConcatContents() as callback and returns a
     * string version of the JSON data structures.
     *
     * @param string $key
     * @param string $value
     * @since  0.5.3
     *
     * @return array tuple [key,value]
     */
    private function jsonDecodeValues($key, $value)
    {
        $decodedKey   = json_decode($key);
        $decodedValue = json_decode($value);

        if ($decodedValue && is_array($decodedValue) || is_object($decodedValue)) {
            array_walk_recursive($decodedValue, array($this, 'jsonConcatContents'));
            $value = $this->tmpJsonString;
        } else {
            $this->tmpJsonString .=  " " . $decodedValue . "\n";
        }

        if ($decodedKey && is_array($decodedKey) || is_object($decodedKey)) {
            array_walk_recursive($decodedKey, array($this, 'jsonConcatContents'));
            $key = $this->tmpJsonString;
        } else {
            $this->tmpJsonString .=  " " . $decodedKey . "\n";
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
            $this->jsonDecodeValues(json_encode($key), json_encode($value));
        }
    }

    /**
     * Sets exception array
     *
     * @param string[]|string $exceptions the thrown exceptions
     *
     * @return void
     */
    public function setExceptions($exceptions)
    {
        $this->exceptions = (array) $exceptions;
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
     * @param string[]|string $html the fields containing html
     * @since 0.5
     *
     * @return void
     */
    public function setHtml($html)
    {
        $this->html = (array) $html;
    }

    /**
     * Adds a value to the html array
     *
     * @since 0.5
     *
     * @param mixed $value
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
     * @param string[]|string $json the fields containing json
     * @since 0.5.3
     *
     * @return void
     */
    public function setJson($json)
    {
        $this->json = (array) $json;
    }

    /**
     * Adds a value to the json array
     *
     * @param  string $value the value containing JSON data
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
}
