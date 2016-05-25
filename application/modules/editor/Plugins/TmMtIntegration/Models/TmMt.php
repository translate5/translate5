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

/**
 * Tmmt Entity Object
 * 
 * @method integer getId() getId()
 * @method void setId() setId(integer $id)
 * @method string getName() getName()
 * @method void setName() setName(string $name)
 * @method string getSourceLang() getSourceLang()
 * @method void setSourceLang() setSourceLang(integer $id)
 * @method string getTargetLang() getTargetLang()
 * @method void setTargetLang() setTargetLang(integer $id)
 * @method string getColor() getColor()
 * @method void setColor() setColor(string $color)
 * @method string getResourceId() getResourceId()
 * @method void setResourceId() setResourceId(integer $resourceId)
 * @method string getResourceType() getResourceType()
 * @method void setResourceType() setResourceType(string $type)
 * @method string getResourceName() getResourceName()
 * @method void setResourceName() setResourceName(string $resName)
 */
class editor_Plugins_TmMtIntegration_Models_TmMt extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Plugins_TmMtIntegration_Models_Db_TmMt';
    protected $validatorInstanceClass = 'editor_Plugins_TmMtIntegration_Models_Validator_TmMt';
    
    public function loadByAssociatedTask(editor_Models_Task $task) {
        $assocDb = new editor_Plugins_TmMtIntegration_Models_Db_Taskassoc();
        $assocName = $assocDb->info($assocDb::NAME);
        $s = $this->db->select()
            ->from($this->db, '*')
            //->setIntegrityCheck(false)
            ->join($assocName, $assocName.'.`tmmtId` = '.$this->db->info($assocDb::NAME).'.`id`', '')
            ->where($assocName.'.`taskGuid` = ?', $task->getTaskGuid());
        return $this->db->fetchAll($s)->toArray();
    }
    
    /**
     * FIXME remove me after renaming resourceType to service
     * @return string
     */
    public function getService() {
        return $this->getResourceType();
    }
    
    /**
     * FIXME remove me after renaming resourceType to service
     * @return string
     */
    public function getServiceName() {
        return $this->getResourceName();
    }
    
    /**
     * FIXME remove me after renaming resourceType to service
     * @return string
     */
    public function setService($serviceName) {
        return $this->setResourceType($serviceName);
    }
    
    /**
     * FIXME remove me after renaming resourceType to service
     * @return string
     */
    public function setServiceName($name) {
        return $this->setResourceName($name);
    }
}