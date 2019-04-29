<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * Abstract Base LanguageResource Resources manager
 * Not one instance per resource, but one instance providing data to all resources of one service
 */
abstract class editor_Services_ServiceAbstract {
    const DEFAULT_COLOR = 'ff0000';
    
    protected $resourceClass = 'editor_Models_LanguageResources_Resource';
    protected $resources = array();
    
    /**
     * To be overwritten
     */
    abstract public function __construct();
    
    /**
     * returns the name of this resources / service
     * @return string
     */
    abstract public function getName();
    
    /**
     * Creates a new Resource instance and adds it to the internal list
     * the given arguments are given as construct parameters to the configured resourceClass
     * @param array $constructorArgs
     */
    protected function addResource(array $constructorArgs) {
        $res = ZfExtended_Factory::get($this->resourceClass, $constructorArgs);
        /* @var $res editor_Models_LanguageResources_Resource */
        $res->setService($this->getName(), $this->getServiceNamespace(), static::DEFAULT_COLOR);
        $this->resources[] = $res;
    }
    
    /**
     * Adds resource for a given list of URLs
     * @see self::addResource
     * @param string $name
     * @param array $urls
     */
    protected function addResourceForeachUrl(string $name, array $urls) {
        $i = 1;
        $service = $this->getServiceNamespace();
        foreach ($urls as $url) {
            $id = $service.'_'.$i++;
            $this->addResource([$id, $name, $url]);
        }
    }
    
    /**
     * returns a list with connector instances, one per resource
     * @return [editor_Models_LanguageResources_Resource]
     */
    public function getResources(){
        return $this->resources;
    }
    
    /**
     * returns the resource to the given resource id
     * @param string $id
     * @return editor_Models_LanguageResources_Resource|NULL
     */
    public function getResourceById(string $id) {
        foreach ($this->resources as $resource) {
            if($id === $resource->getId()) {
                return $resource;
            }
        }
        return null;
    }
    
    /**
     * returns the service namespace for later invocation
     */
    public function getServiceNamespace() {
        $className = get_class($this);
        $pos = strrpos($className, '_');
        return substr($className, 0, $pos);
    }
}