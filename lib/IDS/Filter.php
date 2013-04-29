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

/**
 * PHPIDS Filter object
 *
 * Each object of this class serves as a container for a specific filter. The
 * object provides methods to get information about this particular filter and
 * also to match an arbitrary string against it.
 *
 * @category  Security
 * @package   PHPIDS
 * @author    Christian Matthies <ch0012@gmail.com>
 * @author    Mario Heiderich <mario.heiderich@gmail.com>
 * @author    Lars Strojny <lars@strojny.net>
 * @copyright 2007-2009 The PHPIDS Group
 * @license   http://www.gnu.org/licenses/lgpl.html LGPL
 * @link      http://php-ids.org/
 * @since     Version 0.4
 */
class Filter
{
    /**
     * Filter rule
     *
     * @var string
     */
    protected $rule;

    /**
     * List of tags of the filter
     *
     * @var string[]|array
     */
    protected $tags = array();

    /**
     * Filter impact level
     *
     * @var integer
     */
    protected $impact = 0;

    /**
     * Filter description
     *
     * @var string
     */
    protected $description = '';

    /**
     * Constructor
     *
     * @param integer           $id          filter id
     * @param string            $rule        filter rule
     * @param string            $description filter description
     * @param string[]|array    $tags        list of tags
     * @param integer           $impact      filter impact level
     *
     * @return \IDS\Filter
     */
    public function __construct($id, $rule, $description, array $tags, $impact)
    {
        $this->id          = $id;
        $this->rule        = $rule;
        $this->tags        = $tags;
        $this->impact      = $impact;
        $this->description = $description;
    }

    /**
     * Matches a string against current filter
     *
     * Matches given string against the filter rule the specific object of this
     * class represents
     *
     * @param string $input the string input to match
     *
     * @throws \InvalidArgumentException if argument is no string
     * @return boolean
     */
    public function match($input)
    {
        if (!is_string($input)) {
            throw new \InvalidArgumentException(
                'Invalid argument. Expected a string, received ' . gettype($input)
            );
        }

        return (bool) preg_match('/' . $this->getRule() . '/ms', strtolower($input));
    }

    /**
     * Returns filter description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Return list of affected tags
     *
     * Each filter rule is concerned with a certain kind of attack vectors.
     * This method returns those affected kinds.
     *
     * @return string[]|array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Returns filter rule
     *
     * @return string
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * Get filter impact level
     *
     * @return integer
     */
    public function getImpact()
    {
        return $this->impact;
    }

    /**
     * Get filter ID
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}
