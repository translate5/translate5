<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3
			 http://www.gnu.org/licenses/agpl.html

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
 * @method string getSourceLangRfc5646() getSourceLangRfc5646()
 * @method void setSourceLangRfc5646() setSourceLangRfc5646(string $lang)
 * @method string getTargetLang() getTargetLang()
 * @method void setTargetLang() setTargetLang(integer $id)
 * @method string getTargetLangRfc5646() getTargetLangRfc5646()
 * @method void setTargetLangRfc5646() setTargetLangRfc5646(string $lang)
 * @method string getColor() getColor()
 * @method void setColor() setColor(string $color)
 * @method string getResourceId() getResourceId()
 * @method void setResourceId() setResourceId(integer $resourceId)
 * @method string getServiceType() getServiceType()
 * @method void setServiceType() setServiceType(string $type)
 * @method string getServiceName() getServiceName()
 * @method void setServiceName() setServiceName(string $resName)
 * @method string getFileName() getFileName()
 * @method void setFileName() setFileName(string $name)
 * 
 */
class editor_Plugins_MatchResource_Models_TmMt extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Plugins_MatchResource_Models_Db_TmMt';
    protected $validatorInstanceClass = 'editor_Plugins_MatchResource_Models_Validator_TmMt';
    
    /**
     * loads the task to tmmt assocs by a taskguid
     * @param string $taskGuid
     * @return array
     */
    public function loadByAssociatedTaskGuid(string $taskGuid) {
        return $this->loadByAssociatedTaskGuidList(array($taskGuid));
    }
    
    /**
     * loads the task to tmmt assocs by taskguid
     * @param string $taskGuid
     * @return array
     */
    public function loadByAssociatedTaskGuidList(array $taskGuidList) {
        $assocDb = new editor_Plugins_MatchResource_Models_Db_Taskassoc();
        $assocName = $assocDb->info($assocDb::NAME);
        $s = $this->db->select()
            ->from($this->db, array('*',$assocName.'.taskGuid', $assocName.'.segmentsUpdateable'))
            ->setIntegrityCheck(false)
            ->join($assocName, $assocName.'.`tmmtId` = '.$this->db->info($assocDb::NAME).'.`id`', '')
            ->where($assocName.'.`taskGuid` in (?)', $taskGuidList);
        return $this->db->fetchAll($s)->toArray(); 
    }
    
    /**
     * returns the resource used by this tmmt instance
     * @return editor_Plugins_MatchResource_Models_Resource
     */
    public function getResource() {
        $manager = ZfExtended_Factory::get('editor_Plugins_MatchResource_Services_Manager');
        /* @var $manager editor_Plugins_MatchResource_Services_Manager */
        $res = $manager->getResource($this);
        if(empty($res)) {
            $log = ZfExtended_Factory::get('ZfExtended_Log');
            /* @var $log ZfExtended_Log */
            $msg = 'Configured MatchResource Resource not found for Tmmt '.$this->getName().' with ID '.$this->getId().' the resource id was: '.$this->getResourceId();
            $msg .= "\n".'Maybe the resource config of the underlying Match Resource Service was changed / removed.';
            $log->logError('Configured MatchResource Resource not found', $msg);
            throw new ZfExtended_Models_Entity_NotFoundException('Die ursprünglich konfigurierte TM / MT Resource ist nicht mehr vorhanden!');
        }
        return $res;
    }
}