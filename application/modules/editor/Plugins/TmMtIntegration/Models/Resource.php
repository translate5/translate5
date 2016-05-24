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
class editor_Plugins_TmMtIntegration_Models_Resource {
    /**
     * name of the resource
     * @var string
     */
    protected $name;
    
    protected $filebased = false;
    
    protected $fieldsForController = array('id', 'name', 'filebased');
    
    public function __construct($id, $name, $filebased = false) {
        $this->id = $id;
        $this->name = $name;
        $this->filebased = $filebased;
    }
    
    public function getId() {
        return $this->id;
    }
    
    /**
     * returns the resource name
     */
    public function getName() {
        return $this->name;
    }
    
    /**
     * returns the resource name
     */
    public function getFilebased() {
        return $this->filebased;
    }
    
    /**
     * returns the resource as stdClass data object for the ResourceController
     * @return stdClass
     */
    public function getDataObject() {
        $data = new stdClass();
        foreach($this->fieldsForController as $key) {
            $method = 'get'.ucfirst($key);
            $data->$key = $this->$method();
        }
        return $data;
    }
}