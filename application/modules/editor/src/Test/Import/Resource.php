<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 translate5: Please see http://www.translate5.net/plugin-exception.txt or
 plugin-exception.txt in the root folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

namespace MittagQI\Translate5\Test\Import;

use MittagQI\Translate5\Test\Api\Helper;

/**
 * General base-Class for all resources
 */
abstract class Resource
{
    protected string $_name;
    protected int $_index;
    protected bool $_requested = false;

    /**
     * @param string $testClass
     * @param int $index
     */
    public function __construct(string $testClass, int $index)
    {
        // fixed recognizable naming scheme for a resource name
        $this->_name = $this->createName($testClass, $index);
        $this->_index = $index;
        // for lazyness: define the name for those resources, that have names
        if (property_exists($this, 'name')) {
            $this->name = $this->_name;
        }
    }

    /**
     * Creates the name of a resource
     * @param string $testClass
     * @param int $resourceIndex
     * @return string
     */
    protected function createName(string $testClass, int $resourceIndex): string
    {
        return $this->purifyClassname(static::class) . '-' . $this->purifyClassname($testClass) . '-' . $resourceIndex;
    }

    /**
     * prequesite that we have tests from namespaces at some point
     * @param string $className
     * @return string
     */
    protected function purifyClassname(string $className): string
    {
        if (str_contains($className, '\\')) {
            $parts = explode('\\', $className);
            return array_pop($parts);
        }
        return $className;
    }

    /**
     * Retrieves the request-data for the resource
     * @return array
     */
    public function getRequestParams(): array
    {
        $props = [];
        foreach (get_object_vars($this) as $name => $val) {
            // exclude internal props
            if (!str_starts_with($name, '_')) {
                $props[$name] = $val;
            }
        }
        return $props;
    }

    /**
     * Applies the requested data back to this class after request
     * This dynamically adds more props
     * @param \stdClass $result
     */
    public function applyResult(\stdClass $result)
    {
        // error_log("APPLY RESULTS:\n".json_encode($result, JSON_PRETTY_PRINT));
        foreach (get_object_vars($result) as $name => $val) {
            if (!str_starts_with($name, '_')) {
                $this->$name = $val;
            }
        }
        $this->_requested = true;
    }

    /**
     * Checks if a requested result is valid
     * @param \stdClass|array $result
     * @param Helper $api
     * @return bool
     */
    protected function validateResult($result, Helper $api): bool
    {
        if (!is_object($result) || property_exists($result, 'error')) {
            $error = 'The ' . get_class($this) . ' with the internal name "' . $this->_name . '" failed to import';
            if (property_exists($result, 'error')) {
                $error .= ' [ ' . $result->error . ' ]';
            }
            $api->getTest()::fail($error);
            return false;
        }
        $this->applyResult($result);
        return true;
    }

    /**
     * Sets a property of the recource
     * @param string $name
     * @param $val
     * @return $this
     * @throws Exception
     */
    public function setProperty(string $name, $val): Resource
    {
        if (str_starts_with($name, '_')) {
            throw new Exception('Resource::setProperty: you can not set internal vars');
        }
        if (!property_exists($this, $name)) {
            throw new Exception('Resource::setProperty: property "' . $name . '" does not exist');
        }
        $this->$name = $val;
        return $this;
    }

    /**
     * Adds a property of the recource that will be added to the request params
     * @param string $name
     * @param $val
     * @return $this
     * @throws Exception
     */
    public function addProperty(string $name, $val): Resource
    {
        if (str_starts_with($name, '_')) {
            throw new Exception('Resource::addProperty: you can not add internal vars');
        }
        $this->$name = $val;
        return $this;
    }

    /**
     * Checks if there is a resource
     * @param string $name
     * @return bool
     */
    public function hasProperty(string $name): bool
    {
        if (str_starts_with($name, '_')) {
            return false;
        }
        return property_exists($this, $name);
    }

    /**
     * @param string $name
     * @return mixed
     * @throws Exception
     */
    public function getProperty(string $name)
    {
        if (!property_exists($this, $name)) {
            throw new Exception('Resource::getProperty: property "' . $name . '" does not exist');
        }
        return $this->$name;
    }

    /**
     * The id can generally only be accessed after the resource was requested!
     * @return int
     * @throws Exception
     */
    public function getId(): int
    {
        return intval($this->getProperty('id'));
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getName(): string
    {
        return strval($this->getProperty('name'));
    }

    /**
     * @return int
     */
    public function getInternalIndex(): int
    {
        return $this->_index;
    }

    /**
     * @return int
     */
    public function getInternalName(): string
    {
        return $this->_name;
    }

    /**
     * Certain functions of resources can only be called after import
     * @param string $additionalMessage
     * @throws Exception
     */
    protected function checkImported(string $additionalMessage=''){
        if(!$this->_requested){
            throw new Exception(trim('The '.get_class($this).' was not yet imported '.$additionalMessage));
        }
    }

    /**
     * Imports the resource in the setup-phase of the test
     * @param Helper $api
     * @param Config $config
     * @return mixed
     */
    abstract public function import(Helper $api, Config $config): void;

    /**
     * Removes the imported resources in the teardown-phase of the test
     * @param Helper $api
     * @param Config $config
     * @return mixed
     */
    abstract public function cleanup(Helper $api, Config $config): void;
}
