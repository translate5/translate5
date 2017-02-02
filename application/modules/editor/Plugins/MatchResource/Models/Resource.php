<?php
/*
 START LICENSE AND COPYRIGHT

This file is part of translate5

Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
as published by the Free Software Foundation and appearing in the file agpl3-license.txt
included in the packaging of this file.  Please review the following information
to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
http://www.gnu.org/licenses/agpl.html

There is a plugin exception available for use with this release of translate5 for
open source applications that are distributed under a license other than AGPL:
Please see Open Source License Exception for Development of Plugins for translate5
http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
folder of translate5.

@copyright  Marc Mittag, MittagQI - Quality Informatics
@author     MittagQI - Quality Informatics
@license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/
class editor_Plugins_MatchResource_Models_Resource {
    /**
     * name of the resource
     * @var string
     */
    protected $name;
    
    /**
     * Match Type in the sense of the matchrate type
     * @var string
     */
    protected $type = editor_Models_Segment_MatchRateType::TYPE_TM;
    
    /**
     * Flag if this resource is filebased or not
     * service can set this flag as it needs it. for the case if some new services added in the future
     * can have filebased resources and non filebased ones at the same time
     * Must be overridden by class extension
     * @var boolean
     */
    protected $filebased = true;
    
    /**
     * Flag if this resource can be triggered for search requests
     * Must be overridden by class extension
     * @var boolean
     */
    protected $searchable = true;
    
    /**
     * Flag if edited matches can ba saved back to this resource
     * Must be overridden by class extension
     * @var boolean
     */
    protected $writable = true;
    
    protected $service;
    
    protected $serviceName;
    
    /**
     * index is the fieldname for export values in the controller
     * value is the internal fieldname / getter
     * @var unknown
     */
    protected $fieldsForController = array(
            'id' => 'id',
            'name' => 'name',
            'serviceName' => 'service',
            'serviceType' => 'serviceType',
            'filebased' => 'filebased',
            'searchable' => 'searchable',
            'writable' => 'writable',
            'defaultColor' => 'defaultColor',
    );
    
    /**
     * Resource URL
     * @var string
     */
    protected $url;
    
    public function __construct($id, $name, $url) {
        $this->id = $id;
        $this->name = $name.' - '.$url;
        $this->url = $url;
    }
    
    public function getId() {
        return $this->id;
    }
    
    /**
     * returns the resource name
     * @return string
     */
    public function getName() {
        return $this->name;
    }
    
    /**
     * returns if resource is filebased or not
     * @return boolean
     */
    public function getFilebased() {
        return $this->filebased;
    }
    
    /**
     * returns if resource is searchable or not
     * @return boolean
     */
    public function getSearchable() {
        return $this->searchable;
    }
    
    /**
     * returns if resource is writable or not
     * @return boolean
     */
    public function getWritable() {
        return $this->writable;
    }
    
    /**
     * returns the service name
     * @return string
     */
    public function getService() {
        return $this->service;
    }
    
    /**
     * returns the service type
     * @return string
     */
    public function getServiceType() {
        return $this->serviceType;
    }

    /**
     * returns the match rate type
     * @return string
     */
    public function getType() {
        return $this->type;
    }
    
    /**
     * returns the service type
     * @return string
     */
    public function getDefaultColor() {
        return $this->defaultColor;
    }
    
    /**
     * returns true if the resource can deal with the given source language
     * returns true per default, must be implemented in the service specific resource classes
     * @param editor_Models_Languages $sourceLang
     * @return boolean
     */
    public function hasSourceLang(editor_Models_Languages $sourceLang) {
        return true;
    }
    
    /**
     * returns true if the resource can deal with the given target language
     * returns true per default, must be implemented in the service specific resource classes
     * @param editor_Models_Languages $targetLang
     * @return boolean
     */
    public function hasTargetLang(editor_Models_Languages $targetLang) {
        return true;
    }
    
    /**
     * sets the service type
     * @param string $name
     * @param string $type
     * @param string $defaultColor
     */
    public function setService(string $name, string $type, string $defaultColor) {
        $this->service = $name;
        $this->serviceType = $type;
        $this->defaultColor = $defaultColor;
    }
    
    /**
     * returns the configured URL
     */
    public function getUrl() {
        return $this->url;
    }
    
    /**
     * returns the resource as stdClass data object for the ResourceController
     * @return stdClass
     */
    public function getDataObject() {
        $data = new stdClass();
        foreach($this->fieldsForController as $key => $index) {
            $method = 'get'.ucfirst($index);
            $data->$key = $this->$method();
        }
        return $data;
    }
    
    /**
     * Returns just the resources meta data
     * @return boolean[]
     */
    public function getMetaData() {
        return [
            'writable' => $this->writable,
            'searchable' => $this->searchable,
            'filebased' => $this->filebased,
        ];
    }
}