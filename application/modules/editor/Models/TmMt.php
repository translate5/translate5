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
 * @method integer getDefaultCustomer() getDefaultCustomer()
 * @method void setDefaultCustomer() setDefaultCustomer(integer $defaultCustomer)
 * 
 */
class editor_Models_TmMt extends ZfExtended_Models_Entity_Abstract {
    
    // set as match rate type when matchrate was changed
    const MATCH_RATE_TYPE_EDITED = 'matchresourceusage';
    
    //set by changealike editor
    const MATCH_RATE_TYPE_EDITED_AUTO = 'matchresourceusageauto';
    
    protected $dbInstanceClass = 'editor_Models_Db_TmMt';
    protected $validatorInstanceClass = 'editor_Models_Validator_TmMt';
    
    /***
     * Load all resources associated customers of a user
     * @return array|array
     */
    public function loadByUserCustomerAssocs(){
        $sessionUser = new Zend_Session_Namespace('user');
        $userModel=ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $userModel ZfExtended_Models_User */
        $userModel->load($sessionUser->data->id);
        if($userModel->getCustomers()!==null && !empty($userModel->getCustomers())){
            //get the customers assigned to the user
            $customers= trim($userModel->getCustomers(),",");
            $customers=explode(',', $customers);
            
            //load all tmmts associated with the $customers
            $s = $this->db->select()
            ->setIntegrityCheck(false)
            ->from(array('lr' => 'LEK_languageresources_tmmt'))
            ->join(array('lrca' => 'LEK_languageresources_customerassoc'), 'lr.id = lrca.languageResourceId', [])
            ->where('lrca.customerid IN(?)',$customers)
            ->group('lr.id');
            return $this->db->fetchAll($s)->toArray();
            
        }
        return [];
    }
    
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
        $assocDb = new editor_Models_Db_Taskassoc();
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
     * @return editor_Models_Resource
     */
    public function getResource() {
        $manager = ZfExtended_Factory::get('editor_Services_Manager');
        /* @var $manager editor_Services_Manager */
        $res = $manager->getResource($this);
        if(empty($res)) {
            $log = ZfExtended_Factory::get('ZfExtended_Log');
            /* @var $log ZfExtended_Log */
            $msg = 'Configured LanguageResource Resource not found for Tmmt '.$this->getName().' with ID '.$this->getId().' the resource id was: '.$this->getResourceId();
            $msg .= "\n".'Maybe the resource config of the underlying Language Resource Service was changed / removed.';
            $log->logError('Configured LanguageResource Resource not found', $msg);
            throw new ZfExtended_Models_Entity_NotFoundException('Die ursprünglich konfigurierte TM / MT Resource ist nicht mehr vorhanden!');
        }
        return $res;
    }
    
    /**
     * checks if the given tmmt (and segmentid - optional) is usable by the given task
     * 
     * @param string $taskGuid
     * @param integer $tmmtId
     * @param editor_Models_Segment $segment
     * @throws ZfExtended_Models_Entity_NoAccessException
     * 
     */
    public function checkTaskAndTmmtAccess(string $taskGuid,integer $tmmtId, editor_Models_Segment $segment = null) {
        
        //checks if the queried tmmt is associated to the task:
        $tmmtTaskAssoc = ZfExtended_Factory::get('editor_Models_Taskassoc');
        /* @var $tmmtTaskAssoc editor_Models_Taskassoc */
        try {
            //for security reasons a service can only be queried when a valid task association exists and this task is loaded
            // that means the user has also access to the service. If not then not!
            $tmmtTaskAssoc->loadByTaskGuidAndTm($taskGuid, $tmmtId);
        } catch(ZfExtended_Models_Entity_NotFoundException $e) {
            throw new ZfExtended_Models_Entity_NoAccessException(null, null, $e);
        }
        
        if(is_null($segment)) {
            return;
        }
        
        //check taskGuid of segment against loaded taskguid for security reasons
        if ($taskGuid !== $segment->getTaskGuid()) {
            throw new ZfExtended_Models_Entity_NoAccessException();
        }
    }
}