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
namespace IDS\Filter;

use IDS\Init;
use IDS\Caching\CacheFactory;

/**
 * Filter Storage
 *
 * This class provides various default functions for gathering filter patterns
 * to be used later on by the detection mechanism. You might extend this class
 * to your requirements.
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
class Storage
{
    /**
     * Filter source file
     *
     * @var string
     */
    protected $source = null;

    /**
     * Holds caching settings
     *
     * @var array
     */
    protected $cacheSettings = null;

    /**
     * Cache container
     *
     * @var object IDS_Caching wrapper
     */
    protected $cache = null;

    /**
     * Filter container
     *
     * @var array
     */
    protected $filterSet = array();

    /**
     * Constructor
     *
     * Loads filters based on provided IDS_Init settings.
     *
     * @param object $init IDS_Init instance
     *
     * @throws \InvalidArgumentException if unsupported filter type is given
     * @return void
     */
    final public function __construct(Init $init)
    {
        if ($init->config) {

            $caching      = isset($init->config['Caching']['caching']) ? $init->config['Caching']['caching'] : 'none';

            $type         = $init->config['General']['filter_type'];
            $this->source = $init->getBasePath(). $init->config['General']['filter_path'];

            if ($caching && $caching !== 'none') {
                $this->cacheSettings = $init->config['Caching'];
                $this->cache = CacheFactory::factory($init, 'storage');
            }

            switch ($type) {
                case 'xml':
                    return $this->getFilterFromXML();
                case 'json':
                    return $this->getFilterFromJson();
                default:
                    throw new \InvalidArgumentException('Unsupported filter type.');
            }
        }
    }

    /**
     * Sets the filter array
     *
     * @param array $filterSet array containing multiple IDS_Filter instances
     *
     * @return object $this
     */
    final public function setFilterSet($filterSet)
    {
        foreach ($filterSet as $filter) {
            $this->addFilter($filter);
        }

        return $this;
    }

    /**
     * Returns registered filters
     *
     * @return array
     */
    final public function getFilterSet()
    {
        return $this->filterSet;
    }

    /**
     * Adds a filter
     *
     * @param object $filter IDS_Filter instance
     *
     * @return object $this
     */
    final public function addFilter(\IDS\Filter $filter)
    {
        $this->filterSet[] = $filter;

        return $this;
    }

    /**
     * Checks if any filters are cached
     *
     * @return mixed $filters cached filters or false
     */
    private function isCached()
    {
        $filters = false;

        if ($this->cacheSettings) {
            if ($this->cache) {
                $filters = $this->cache->getCache();
            }
        }

        return $filters;
    }

    /**
     * Loads filters from XML using SimpleXML
     *
     * This function parses the provided source file and stores the result.
     * If caching mode is enabled the result will be cached to increase
     * the performance.
     *
     * @throws \InvalidArgumentException if source file doesn't exist
     * @throws \RuntimeException if problems with fetching the XML data occur
     * @return object    $this
     */
    public function getFilterFromXML()
    {
        if (extension_loaded('SimpleXML')) {

            /*
             * See if filters are already available in the cache
             */
            $filters = $this->isCached();

            /*
             * If they aren't, parse the source file
             */
            if (!$filters) {

                if (!file_exists($this->source)) {
                    throw new \InvalidArgumentException(
                        sprintf('Invalid config: %s doesn\'t exist.', $this->source)
                    );
                }

                if (LIBXML_VERSION >= 20621) {
                    $filters = simplexml_load_file($this->source, null, LIBXML_COMPACT);
                } else {
                    $filters = simplexml_load_file($this->source);
                }
            }

            /*
             * In case we still don't have any filters loaded and exception
             * will be thrown
             */
            if (empty($filters)) {
                throw new \RuntimeException(
                    'XML data could not be loaded.' .
                    ' Make sure you specified the correct path.'
                );
            }

            /*
             * Now the storage will be filled with IDS_Filter objects
             */
            $nocache = $filters instanceof \SimpleXMLElement;
            
            if ($nocache)
            {
                // build filters and cache them for re-use on next run
                $data    = array();
                $filters = $filters->filter;
                
                foreach ($filters as $filter) {
                    $id          = (string) $filter->id;
                    $rule        = (string) $filter->rule;
                    $impact      = (string) $filter->impact;
                    $tags        = array_values((array) $filter->tags);
                    $description = (string) $filter->description;
                
                    $this->addFilter(
                            new \IDS\Filter(
                                    $id,
                                    $rule,
                                    $description,
                                    (array) $tags[0],
                                    (int) $impact
                            )
                    );
                
                    $data[] = array(
                            'id'          => $id,
                            'rule'        => $rule,
                            'impact'      => $impact,
                            'tags'        => $tags,
                            'description' => $description
                    );
                }
                
                /*
                 * If caching is enabled, the fetched data will be cached
                */
                if ($this->cacheSettings) {
                    $this->cache->setCache($data);
                }
                
            } else {
            
                // build filters from cached content
                $this->addFiltersFromArray($filters);
            }

            return $this;
        }

        throw new \RuntimeException('SimpleXML is not loaded.');
    }

    /**
     * Loads filters from Json file using ext/Json
     *
     * This function parses the provided source file and stores the result.
     * If caching mode is enabled the result will be cached to increase
     * the performance.
     *
     * @throws \RuntimeException if problems with fetching the JSON data occur
     * @return object    $this
     */
    public function getFilterFromJson()
    {

        if (extension_loaded('Json')) {

            /*
             * See if filters are already available in the cache
             */
            $filters = $this->isCached();

            /*
             * If they aren't, parse the source file
             */
            if (!$filters) {
                if (!file_exists($this->source)) {
                    throw new \InvalidArgumentException(
                        sprintf('Invalid config: %s doesn\'t exist.', $this->source)
                    );
                }
                $filters = json_decode(file_get_contents($this->source));
            }

            if (!$filters) {
                throw new \RuntimeException('JSON data could not be loaded. Make sure you specified the correct path.');
            }

            /*
             * Now the storage will be filled with IDS_Filter objects
             */
            $nocache = !is_array($filters);
            
            if ($nocache) {
                
                // build filters and cache them for re-use on next run
                $data    = array();
                $filters = $filters->filters->filter;
                
                foreach ($filters as $filter) {

                    $id          = (string) $filter->id;
                    $rule        = (string) $filter->rule;
                    $impact      = (string) $filter->impact;
                    $tags        = array_values((array) $filter->tags);
                    $description = (string) $filter->description;
    
                    $this->addFilter(
                        new \IDS\Filter(
                            $id,
                            $rule,
                            $description,
                            (array) $tags[0],
                            (int) $impact
                        )
                    );
    
                    $data[] = array(
                        'id'          => $id,
                        'rule'        => $rule,
                        'impact'      => $impact,
                        'tags'        => $tags,
                        'description' => $description
                    );
                }
    
                /*
                 * If caching is enabled, the fetched data will be cached
                 */
                if ($this->cacheSettings) {
                    $this->cache->setCache($data);
                }
                
            } else {
                
                // build filters from cached content
                $this->addFiltersFromArray($filters);
            }

            return $this;
        }

        throw new \RuntimeException('json extension is not loaded.');
    }
    
    /**
     * This functions adds an array of filters to the IDS_Storage object.
     * Each entry within the array is expected to be an simple array containing all parts of the filter.
     * 
     * @param array $filters
     */
    private function addFiltersFromArray(array $filters)
    {
        foreach ($filters as $filter) {
        
            $id          = $filter['id'];
            $rule        = $filter['rule'];
            $impact      = $filter['impact'];
            $tags        = $filter['tags'];
            $description = $filter['description'];
        
            $this->addFilter(
                new \IDS\Filter(
                    $id,
                    $rule,
                    $description,
                    (array) $tags[0],
                    (int) $impact
                )
            );
        }
    }
}
