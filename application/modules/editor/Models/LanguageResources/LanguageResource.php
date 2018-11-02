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
 * Languageresources Entity Object
 * 
 * @method integer getId() getId()
 * @method void setId() setId(integer $id)
 * @method string getName() getName()
 * @method void setName() setName(string $name)
 * @method string getColor() getColor()
 * @method void setColor() setColor(string $color)
 * @method string getResourceId() getResourceId()
 * @method void setResourceId() setResourceId(integer $resourceId)
 * @method string getServiceType() getServiceType()
 * @method void setServiceType() setServiceType(string $type)
 * @method string getServiceName() getServiceName()
 * @method void setServiceName() setServiceName(string $resName)
 * @method string getSpecificData() getSpecificData()
 * @method void setSpecificData() setSpecificData(string $value)
 * @method integer getAutoCreatedOnImport() getAutoCreatedOnImport()
 * @method void setAutoCreatedOnImport() setAutoCreatedOnImport(integer $autoCreatedOnImport)
 * @method string getResourceType() getResourceType()
 * @method void setResourceType() setResourceType(string $resourceType)
 */
class editor_Models_LanguageResources_LanguageResource extends ZfExtended_Models_Entity_Abstract {
    
    // set as match rate type when matchrate was changed
    const MATCH_RATE_TYPE_EDITED = 'matchresourceusage';
    
    //set by changealike editor
    const MATCH_RATE_TYPE_EDITED_AUTO = 'matchresourceusageauto';
    
    protected $dbInstanceClass = 'editor_Models_Db_LanguageResources_LanguageResource';
    protected $validatorInstanceClass = 'editor_Models_Validator_LanguageResources_LanguageResource';
    
    
    /***
     * Source lang id helper property
     * @var int
     */
    public $sourceLang;
    
    /***
     * Target lang id helper property
     * @var int
     */
    public $targetLang;
    
    /***
     * Source lang rfc value helper property 
     * @var String
     */
    public $sourceLangRfc5646;
    
    
    /***
     * Target lang rfc value helper property
     * @var String
     */
    public $targetLangRfc5646;
    
    
    /***
     * Load all resources for all available services
     * 
     * @return array
     */
    public function loadAllByServices(){
        $services=ZfExtended_Factory::get('editor_Services_Manager');
        /* @var $services editor_Services_Manager */
        
        //get all service types from the available resources
        $resources=$services->getAllResources();
        $allservices=[];
        foreach ($resources as $resource) {
            /* @var $resource editor_Models_LanguageResources_Resource */
            $allservices[]=$resource->getServiceType();
        }
        $allservices=array_unique($allservices);
        $s=$this->db->select()
        ->where('serviceType IN(?)',$allservices);
        return $this->loadFilterdCustom($s);
    }
    
    /***
     * Get all available language resources for customers of loged user
     * @param boolean $addArrayId : if true(default true), the array key will be the language resource id
     * @return array
     */
    public function getEnginesByAssoc($addArrayId=true){
        
        $serviceManager = ZfExtended_Factory::get('editor_Services_Manager');
        /* @var $serviceManager editor_Services_Manager */
        $resources = $serviceManager->getAllResources();
        $mtRes=[];
        //get all available tm resources
        foreach($resources as $resource) {
            /* @var $resource editor_Models_LanguageResources_Resource */
            if($resource->getType()==editor_Models_Segment_MatchRateType::TYPE_MT && !in_array($resource->getService(), $mtRes)){
                $mtRes[]=$resource->getService();
            }
        }
        
        //filter assoc resources by mt
        $engines=$this->loadByUserCustomerAssocs($mtRes);
        
        //check if results are found
        if(empty($engines)){
            return $engines;
        }
        
        $sdl=ZfExtended_Factory::get('editor_Models_LanguageResources_SdlResources');
        /* @var $sdl editor_Models_LanguageResources_SdlResources */
        
        //merge the data as instanttransalte format
        return $sdl->mergeEngineData($engines,$addArrayId);
    }
    
    /***
     * Load all resources associated customers of a user
     * 
     * @param array $serviceNames: add service name as filter
     * @param string $sourceLang: add source language as filter
     * @param string $targetLang: add target language as filter
     * 
     * @return array|array
     */
    public function loadByUserCustomerAssocs($serviceNames=array(),$sourceLang=null,$targetLang=null){
        $userModel=ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $userModel ZfExtended_Models_User */
        $customers=$userModel->getUserCustomersFromSession();
        if(!empty($customers)){
            
            //each sdlcloud language resource can have only one language combination
            $s=$this->db->select()
            ->from(array('tm' => 'LEK_languageresources'),array('tm.*'))
            ->setIntegrityCheck(false)
            ->join(array('ca' => 'LEK_languageresources_customerassoc'), 'tm.id = ca.languageResourceId', '')
            ->join(array('l' => 'LEK_languageresources_languages'), 'tm.id = l.languageResourceId', array('sourceLang','targetLang','sourceLangRfc5646','targetLangRfc5646'))
            ->where('ca.customerId IN(?)',$customers);

            if(!empty($serviceNames)){
                $s->where('tm.serviceName IN(?)',$serviceNames);
            }
            
            if($sourceLang){
                $s->where('l.sourceLang=?',$sourceLang);
            }
            
            if($targetLang){
                $s->where('l.targetLang=?',$targetLang);
            }
            $s->group('tm.id');
            return $this->db->fetchAll($s)->toArray();
            
        }
        return [];
    }
    
    /**
     * loads the task to languageResource assocs by a taskguid
     * @param string $taskGuid
     * @return array
     */
    public function loadByAssociatedTaskGuid(string $taskGuid) {
        return $this->loadByAssociatedTaskGuidList(array($taskGuid));
    }
    
    /**
     * loads the task to languageResource assocs by taskguid
     * @param string $taskGuid
     * @return array
     */
    public function loadByAssociatedTaskGuidList(array $taskGuidList) {
        if(empty($taskGuidList)){
            return $taskGuidList;
        }
        $assocDb = new editor_Models_Db_Taskassoc();
        $assocName = $assocDb->info($assocDb::NAME);
        $s = $this->db->select()
            ->from($this->db, array('*',$assocName.'.taskGuid', $assocName.'.segmentsUpdateable'))
            ->setIntegrityCheck(false)
            ->join($assocName, $assocName.'.`languageResourceId` = '.$this->db->info($assocDb::NAME).'.`id`', '')
            ->where($assocName.'.`taskGuid` in (?)', $taskGuidList);
        return $this->db->fetchAll($s)->toArray(); 
    }
    
    /**
     * loads the task to languageResource assocs by list of taskGuids and resourceTypes
     * @param array $taskGuid
     * @param array $resourceTypes
     * @return array
     */
    public function loadByAssociatedTaskGuidListAndResourcesType(array $taskGuidList,array $resourceTypes) {
        if(empty($taskGuidList)){
            return $taskGuidList;
        }
        $assocDb = new editor_Models_Db_Taskassoc();
        $tableName=$this->db->info($assocDb::NAME);
        $assocName = $assocDb->info($assocDb::NAME);
        $s = $this->db->select()
        ->from($this->db, array('*',$assocName.'.taskGuid', $assocName.'.segmentsUpdateable'))
        ->setIntegrityCheck(false)
        ->join($assocName, $assocName.'.`languageResourceId` = '.$tableName.'.`id`', '')
        ->where($assocName.'.`taskGuid` IN (?)', $taskGuidList)
        ->where($tableName.'.resourceType IN(?)',$resourceTypes);
        return $this->db->fetchAll($s)->toArray();
    }
    
    /**
     * Get language resource by given resourceId
     * @param string $resourceId
     * @return Ambigous <Zend_Db_Table_Row_Abstract, NULL>
     */
    public function loadByResourceId($resourceId) {
        return $this->loadRow('resourceId = ?', $resourceId);
    }
    
    /**
     * loads the ids, names and additional information of all TMs for the given serviceName
     * @param string $serviceName
     * @return array
     */
    public function loadByServiceName(string $serviceName) {
        $db = $this->db;
        $s = $db->select()
            ->from($db->info($db::NAME), ['id','name','specificData'])
            ->where('LEK_languageresources.serviceName LIKE ?', $serviceName);
        return $this->db->fetchAll($s)->toArray(); 
    }
    
    /**
     * loads the ids and names of all TMs for the given name
     * @param string $name
     * @return array
     */
    public function loadByName(string $name) {
        $db = $this->db;
        $s = $db->select()
        ->from($db->info($db::NAME), ['id','name'])
        ->where('LEK_languageresources.name LIKE ?', $name);
        return $this->db->fetchAll($s)->toArray();
    }
    
    /**
     * returns the resource used by this languageResource instance
     * @return editor_Models_LanguageResources_Resource
     */
    public function getResource() {
        $manager = ZfExtended_Factory::get('editor_Services_Manager');
        /* @var $manager editor_Services_Manager */
        $res = $manager->getResource($this);
        if(empty($res)) {
            $log = ZfExtended_Factory::get('ZfExtended_Log');
            /* @var $log ZfExtended_Log */
            $msg = 'Configured LanguageResource Resource not found for LanguageResource '.$this->getName().' with ID '.$this->getId().' the resource id was: '.$this->getResourceId();
            $msg .= "\n".'Maybe the resource config of the underlying Language Resource Service was changed / removed.';
            $log->logError('Configured LanguageResource Resource not found', $msg);
            throw new ZfExtended_Models_Entity_NotFoundException('Die ursprünglich konfigurierte TM / MT Resource ist nicht mehr vorhanden!');
        }
        return $res;
    }
    
    /**
     * checks if the given languageResource (and segmentid - optional) is usable by the given task
     * 
     * @param string $taskGuid
     * @param integer $languageResourceId
     * @param editor_Models_Segment $segment
     * @throws ZfExtended_Models_Entity_NoAccessException
     * 
     */
    public function checkTaskAndLanguageResourceAccess(string $taskGuid,integer $languageResourceId, editor_Models_Segment $segment = null) {
        
        //checks if the queried languageResource is associated to the task:
        $languageResourceTaskAssoc = ZfExtended_Factory::get('editor_Models_LanguageResources_Taskassoc');
        /* @var $languageResourceTaskAssoc editor_Models_LanguageResources_Taskassoc */
        try {
            //for security reasons a service can only be queried when a valid task association exists and this task is loaded
            // that means the user has also access to the service. If not then not!
            $languageResourceTaskAssoc->loadByTaskGuidAndTm($taskGuid, $languageResourceId);
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
    
    /***
     * Load the exsisting langages for the initialized entity.
     * @param string $fieldName : field which will be returned
     * @throws ZfExtended_ValidateException
     * @return array
     */
    public function getLanguageByField($fieldName){

        //check if the fieldName is defined
        if(empty($fieldName)){
            throw new ZfExtended_ValidateException("Missing field name.");
        }
        
        if($this->getId()==null){
            throw new ZfExtended_ValidateException("Entity id is not set.");
        }
        
        $model=ZfExtended_Factory::get('editor_Models_LanguageResources_Languages');
        /* @var $model editor_Models_LanguageResources_Languages */
        
        //load the existing languages from the languageresource languages table
        $res=$model->loadByLanguageResourceId($this->getId());
        
        if(count($res)==1){
            return $res[0][$fieldName];
        }
        return array_column($res, $fieldName);
    }
    
    /***
     * Get the source lang rfc values from the languageresource language table.
     * Note: the enity id need to be valid
     * @return array|string
     */
    public function getSourceLangRfc5646(){
        if(!$this->sourceLangRfc5646){
            $this->sourceLangRfc5646=$this->getLanguageByField('sourceLangRfc5646');
        }
        return $this->sourceLangRfc5646;
    }
    
    /***
     * Get the target lang rfc values from the languageresource language table.
     * Note: the enity id need to be valid
     * @return array|string
     */
    public function getTargetLangRfc5646(){
        if(!$this->targetLangRfc5646){
            $this->targetLangRfc5646=$this->getLanguageByField('targetLangRfc5646');
        }
        return $this->targetLangRfc5646;
    }
    
    /***
     * Get the source lang id values from the languageresource language table.
     * Note: the enity id need to be valid
     * @return array|string
     */
    public function getSourceLang(){
        if(!$this->sourceLang){
            $this->sourceLang=$this->getLanguageByField('sourceLang');
        }
        return $this->sourceLang;
    }
    
    /***
     * Get the target lang id values from the languageresource language table.
     * Note: the enity id need to be valid
     * @return array|string
     */
    public function getTargetLang(){
        if(!$this->targetLang){
            $this->targetLang=$this->getLanguageByField('targetLang');
        }
        return $this->targetLang;
    }
    
    /***
     * Get specificData field value. The returned value will be json decoded.
     * If $propertyName is provided, only the value for this field will be returned if exisit.
     * @param string $propertyName
     * @return mixed|NULL
     */
    public function getSpecificData($propertyName=null){
        $specificData=$this->__call('getSpecificData', array());
        
        if(empty($specificData)){
            return null;
        }
        //try to decode the data
        try {
            $specificData=json_decode($specificData);
            
            //return the property name value if exist
            if(isset($propertyName)){
                return isset($specificData->$propertyName) ? $specificData->$propertyName : null;
            }
            return $specificData;
        } catch (Exception $e) {
            
        }
        return null;
    }
    
    /***
     * Set the specificData field. The given value will be json encoded.
     * @param string $value
     */
    public function setSpecificData($value){
        $this->__call('setSpecificData', array(
            json_encode($value)
        ));
    }
    
    /***
     * Add specific data by propert name and value. The result will be encoded back to json
     * @param string $propertyName
     * @param mixed $value
     * @return boolean
     */
    public function addSpecificData($propertyName,$value) {
        $specificData=$this->getSpecificData();
        if(empty($specificData)){
            $this->setSpecificData(array($propertyName=>$value));
            return true;
        }
        //set the property name into the specific data
        $specificData->$propertyName=$value;
        $this->setSpecificData($specificData);
        return true;
    }
}