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

/**
 * Languageresources Entity Object
 *
 * @method integer getId() getId()
 * @method void setId() setId(int $id)
 * @method string getLangResUuid() getLangResUuid()
 * @method void setLangResUuid() setLangResUuid(string $guid)
 * @method string getName() getName()
 * @method void setName() setName(string $name)
 * @method string getColor() getColor()
 * @method void setColor() setColor(string $color)
 * @method string getResourceId() getResourceId() The id of the used resource
 * @method void setResourceId() setResourceId(int $resourceId)
 * @method string getServiceType() getServiceType() The PHP class name for the service
 * @method void setServiceType() setServiceType(string $type)
 * @method string getServiceName() getServiceName() The speakable name of the service as configured in the resource
 * @method void setServiceName() setServiceName(string $resName)
 * @method string getResourceType() getResourceType()  tm or mt
 * @method void setResourceType() setResourceType(string $resourceType)
 * @method bool getWriteSource() getWriteSource()
 * @method void setWriteSource() setWriteSource(bool $writeSource)
 *
 */
class editor_Models_LanguageResources_LanguageResource extends ZfExtended_Models_Entity_Abstract {
    use editor_Models_Entity_SpecificDataTrait;
    
    /***
     * set as match rate type when match-rate was changed
     */
    const MATCH_RATE_TYPE_EDITED = 'matchresourceusage';
    
    /***
     * set by change alike editor
     */
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
     * Source language code value helper property
     * @var String
     */
    public $sourceLangCode;
    
    
    /***
     * Target lang code value helper property
     * @var String
     */
    public $targetLangCode;

    /***
     * @var array
     */
    protected array $customers;

    /***
     * Init the language resource instance for given editor_Models_LanguageResources_Resource
     * @param editor_Models_LanguageResources_Resource $resource
     * @return void
     */
    public function initByResource(editor_Models_LanguageResources_Resource $resource){
        $this->createLangResUuid();
        $this->setColor($resource->getDefaultColor());
        $this->setResourceId($resource->getId());
        $this->setServiceType($resource->getServiceType());
        $this->setServiceName($resource->getService());
        $this->setResourceType($resource->getType());
    }

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
        ->where('LEK_languageresources.serviceType IN(?)',$allservices);
        return $this->loadFilterdCustom($s);
    }

    /***
     * Load all language resource by given service name
     * @param string $serviceName
     * @return array
     */
    public function loadByService(string $serviceName): array
    {
        $s = $this->db->select()
            ->where('LEK_languageresources.serviceName = ?',$serviceName);
        return $this->db->fetchAll($s)->toArray();
    }

    /***
     * Load language resource by given name and type associated with particular task
     *
     * @param string $type
     * @param string $name
     * @param array $data
     * @param editor_Models_Task $task
     *
     * @return array
     */
    public function getByTypeNameAndSpecificDataForTask(
        string $type,
        string $name,
        array $data,
        editor_Models_Task $task
    ): array {
        $s = $this->db
            ->select()
            ->from(['lr' => 'LEK_languageresources'], ['lr.*'])
            ->setIntegrityCheck(false)
            ->joinLeft(
                ['l' => 'LEK_languageresources_languages'],
                'lr.id = l.languageResourceId',
                ['sourceLang','targetLang']
            )
            ->joinLeft(
                ['lca' => 'LEK_languageresources_customerassoc'],
                'lr.id = lca.languageResourceId',
                ['customerId']
            )
            ->where('lr.serviceType = ?', $type)
            ->where('lr.name = ?', $name)
            ->where('lca.customerId = ?', $task->getCustomerId());

        foreach ($data as $key => $value) {
            $s->where('JSON_EXTRACT(lr.specificData, "$.' . $key . '") = ?', $value);
        }

        return $this->db->fetchAll($s)->toArray();
    }
    
    /***
     * Get all available language resources for customers of loged user
     * The result data will in custom format(used in instanttranslate frontend)
     *
     * @param bool $addArrayId : if true(default true), the array key will be the language resource id
     * @param string $resourceType : when given, only available resources of this type will be returned
     * @return array
     */
    public function getAllMergedByAssoc($addArrayId=true,string $resourceType=null){
        
        $serviceManager = ZfExtended_Factory::get('editor_Services_Manager');
        /* @var $serviceManager editor_Services_Manager */
        $resources = $serviceManager->getAllResources();
        $services=[];
        //get all available tm resources
        foreach($resources as $resource) {
            $tmpType = $resourceType ?? $resource->getType();
            /* @var $resource editor_Models_LanguageResources_Resource */
            if(!in_array($resource->getService(), $services) && $tmpType==$resource->getType()){
                $services[]=$resource->getService();
            }
        }
        
        //filter assoc resources by services
        $engines = $this->loadByUserCustomerAssocs($services);
        //check if results are found
        if(empty($engines)){
            return $engines;
        }
        
        $sdl=ZfExtended_Factory::get('editor_Models_LanguageResources_SdlResources');
        /* @var $sdl editor_Models_LanguageResources_SdlResources */
        
        //merge the data as instanttransalte format
        return $sdl->mergeEngineData($engines,$addArrayId);
    }

    /**
     * Get info about which language resources can be associated with tasks having $targetLangs languages
     * Return value would be like below:
     * [
     *  'targetLang1Id' => [langResource1Id, langResource2Id],
     *  'targetLang2Id' => [langResource1Id, langResource3Id],
     * ]
     *
     * @param int $customerId
     * @param array $targetLangs
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function getUseAsDefaultForTaskAssoc(int $customerId, array $targetLangs) {

        // Get editor_Models_LanguageResources_CustomerAssoc model shortcut
        $lrcaM = ZfExtended_Factory::get('editor_Models_LanguageResources_CustomerAssoc');

        // Fetch `languageResourceId`-values by $customerId, having `useAsDefault`=1
        if (!$languageResourceIds = $lrcaM->loadByCustomerIdsUseAsDefault([$customerId], 'languageResourceId')) {
            return [];
        }

        // Get info about which language resources can be associated with tasks having $targetLangs languages
        return $this->db->getAdapter()->query('
            SELECT DISTINCT `targetLang`, `languageResourceId`
            FROM `LEK_languageresources_languages` 
            WHERE 1
              AND `languageResourceId` IN (' . join(',', $languageResourceIds) . ') 
              AND `targetLang` IN (' . join(',', $targetLangs) . ')
        ')->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_COLUMN);
    }

    /***
     * Load all resources associated customers of a user
     *
     * @param array $serviceNames: add service name as filter
     * @param array $sourceLang: add source languages as filter
     * @param array $targetLang: add target languages as filter
     *
     * @return array|array
     */
    public function loadByUserCustomerAssocs($serviceNames=array(),$sourceLang=array(),$targetLang=array()){
        $customers = ZfExtended_Authentication::getInstance()->getUser()->getCustomersArray();
        if(!empty($customers)){
            
            //each sdlcloud language resource can have only one language combination
            $s=$this->db->select()
            ->from(array('tm' => 'LEK_languageresources'),array('tm.*'))
            ->setIntegrityCheck(false)
            ->join(array('ca' => 'LEK_languageresources_customerassoc'), 'tm.id = ca.languageResourceId', '')
            ->join(array('l' => 'LEK_languageresources_languages'), 'tm.id = l.languageResourceId', array('sourceLang','targetLang','sourceLangCode','targetLangCode'))
            ->where('ca.customerId IN(?)',$customers);

            if(!empty($serviceNames)){
                $s->where('tm.serviceName IN(?)',$serviceNames);
            }
            
            if(!empty($sourceLang)){
                $s->where('l.sourceLang IN(?)',$sourceLang);
            }
            
            if(!empty($targetLang)){
                $s->where('l.targetLang IN(?)',$targetLang);
            }
            $resutl=$this->db->fetchAll($s)->toArray();
            return $this->mergeLanguages($resutl);
            
        }
        return [];
    }
    
    /**
     * loads the task to languageResource assocs by a taskguid
     * @param string $taskGuid
     * @return array
     */
    public function loadByAssociatedTaskGuid(string $taskGuid) {
        return $this->loadByAssociatedTaskGuidList([ $taskGuid ]);
    }
    
    /**
     * loads the task to languageResource assocs by taskguid
     * @param string[] $taskGuidList
     * @return array
     */
    public function loadByAssociatedTaskGuidList(array $taskGuidList) {
        if(empty($taskGuidList)){
            return [];
        }
        $assocDb = new MittagQI\Translate5\LanguageResource\Db\TaskAssociation();
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
        $assocDb = new MittagQI\Translate5\LanguageResource\Db\TaskAssociation();
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
     * loads the language resources to a specific service resource ID (language resource to a specific server (=resource))
     * @param string $serviceResourceId
     * @return array
     */
    public function loadByResourceId(string $serviceResourceId) {
        $s = $this->db->select()->where('resourceId = ?', $serviceResourceId);
        return $this->db->fetchAll($s)->toArray();
    }

    /**
     * loads the language resources to a specific service resource ID (language resource to a specific server (=resource))
     * @param string $uuid
     * @return Zend_Db_Table_Row_Abstract|null
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function loadByUuid(string $uuid): ?Zend_Db_Table_Row_Abstract
    {
        $s = $this->db->select()->where('langResUuid = ?', $uuid);
        $this->row = $this->db->fetchRow($s);
        if(empty($this->row)){
            $this->notFound('#langResUuid '.$uuid);
        }
        return $this->row;
    }

    /**
     * returns the resource used by this languageResource instance
     * @return editor_Models_LanguageResources_Resource
     */
    public function getResource() {
        $manager = ZfExtended_Factory::get('editor_Services_Manager');
        /* @var $manager editor_Services_Manager */
        $res = $manager->getResource($this);
        if(!empty($res)) {
            return $res;
        }
        throw new editor_Services_Exceptions_NoService('E1316', [
            'service' => $this->getServiceName(),
            'languageResource' => $this,
        ]);
    }
    
    /**
     * checks if the given languageResource (and segmentid - optional) is usable by the given task
     *
     * @param string $taskGuid
     * @param int $languageResourceId
     * @param editor_Models_Segment $segment
     * @throws ZfExtended_Models_Entity_NoAccessException
     *
     */
    public function checkTaskAndLanguageResourceAccess(string $taskGuid,int $languageResourceId, editor_Models_Segment $segment = null) {
        
        //checks if the queried languageResource is associated to the task:
        $languageResourceTaskAssoc = ZfExtended_Factory::get('MittagQI\Translate5\LanguageResource\TaskAssociation');
        /* @var $languageResourceTaskAssoc MittagQI\Translate5\LanguageResource\TaskAssociation */
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
     * Get the source lang code from the languageresource language table.
     * Note: the enity id need to be valid
     * @return array|string
     */
    public function getSourceLangCode(){
        if(!$this->sourceLangCode){
            $this->sourceLangCode=$this->getLanguageByField('sourceLangCode');
        }
        return $this->sourceLangCode;
    }
    
    /***
     * Get the target lang code from the languageresource language table.
     * Note: the enity id need to be valid
     * @return array|string
     */
    public function getTargetLangCode(){
        if(!$this->targetLangCode){
            $this->targetLangCode=$this->getLanguageByField('targetLangCode');
        }
        return $this->targetLangCode;
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
     * Get the customers of the current langauge resource
     * @return array
     */
    public function getCustomers(): array
    {
        if(empty($this->customers)){
            /** @var editor_Models_LanguageResources_CustomerAssoc $model */
            $model = ZfExtended_Factory::get('editor_Models_LanguageResources_CustomerAssoc');
            $this->customers = array_column($model->loadByLanguageResourceId($this->getId()),'customerId');
        }
        return $this->customers;
    }
    
    /**
     * creates and sets a random uuid
     */
    public function createLangResUuid() {
        $this->setLangResUuid(ZfExtended_Utils::uuid());
    }
    
    /***
     * Merge the group the languages by language resource. In the return array for each language resource, all available languages ids and
     * language code values will be in separate array.
     * NOTE: the function is used to merge the languages from ungrouped results from "loadByUserCustomerAssocs" function.
     *
     * @param array $languageResources
     * @return array
     */
    private function mergeLanguages(array $languageResources){
        
        $resIndex=array();
        foreach ($languageResources as $key=>$res) {
            $removeMeAfter=true;
            if(!isset($resIndex[$res['id']])){
                //save the index of the language resource
                $resIndex[$res['id']]=$key;
                
                //init the language resource languages array, and save the first language
                $languageResources[$key]['sourceLang']=[];
                $languageResources[$key]['sourceLangCode']=[];
                $languageResources[$key]['sourceLang'][]=$res['sourceLang'];
                $languageResources[$key]['sourceLangCode'][]=$res['sourceLangCode'];
                
                $languageResources[$key]['targetLang']=[];
                $languageResources[$key]['targetLangCode']=[];
                $languageResources[$key]['targetLang'][]=$res['targetLang'];
                $languageResources[$key]['targetLangCode'][]=$res['targetLangCode'];
                
                $removeMeAfter=false;
            }
            
            //check if the language allready exist for the language resource
            if(!in_array($res['sourceLang'], $languageResources[$resIndex[$res['id']]]['sourceLang'])){
                $languageResources[$resIndex[$res['id']]]['sourceLang'][]=$res['sourceLang'];
                $languageResources[$resIndex[$res['id']]]['sourceLangCode'][]=$res['sourceLangCode'];
            }
            if(!in_array($res['targetLang'], $languageResources[$resIndex[$res['id']]]['targetLang'])){
                $languageResources[$resIndex[$res['id']]]['targetLang'][]=$res['targetLang'];
                $languageResources[$resIndex[$res['id']]]['targetLangCode'][]=$res['targetLangCode'];
            }
            
            //remove the result from the array
            //the only valid result is in $resIndex[$res['id']]
            if($removeMeAfter){
                unset($languageResources[$key]);
            }
        }
        //re-index the array
        return array_values($languageResources);
    }
    
    /**
     * Returns the categories that are assigned to the resource.
     * @return array
     */
    protected function getCategories() {
        $categoryAssoc = ZfExtended_Factory::get('editor_Models_LanguageResources_CategoryAssoc');
        /* @var $categoryAssoc editor_Models_LanguageResources_CategoryAssoc */
        return $categoryAssoc->loadByLanguageResourceId($this->getId());
    }
    
    /**
     * Returns the original ids of the categories that are assigned to the resource.
     * @return array
     */
    public function getOriginalCategoriesIds() {
        $categories = $this->getCategories();
        $categoriesIds = array_column($categories, 'categoryId');
        $m = ZfExtended_Factory::get('editor_Models_Categories');
        /* @var $m editor_Models_Categories */
        $categoriesOriginalIds = [];
        foreach ($categoriesIds as $categoryId) {
            $m->load($categoryId);
            $categoriesOriginalIds[] = $m->getOriginalCategoryId();
        }
        return $categoriesOriginalIds;
    }
    
    /***
     * Is the current resource of type MT (maschine translation)
     * @return boolean
     */
    public function isMt() {
        return $this->getResourceType() == editor_Models_Segment_MatchRateType::TYPE_MT;
    }
    
    /***
     * Is the current resource type of TM (translation memory)
     * @return boolean
     */
    public function isTm() {
        return $this->getResourceType() == editor_Models_Segment_MatchRateType::TYPE_TM;
    }
    
    /***
     * Is the current resource type of term collection
     * @return boolean
     */
    public function isTc() {
        return $this->getResourceType() == editor_Models_Segment_MatchRateType::TYPE_TERM_COLLECTION;
    }

    /**
     * Get termcollection export filename, that tbx-contents will be written to
     */
    static function exportFilename($collectionId) {
        return editor_Models_Import_TermListParser_Tbx::getFilesystemCollectionDir() . 'tc_' . $collectionId . '/export.tbx';
    }

    /**
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function insert() {

        // Call parent
        $return = parent::save();

        // If new termcollection was created
        if ($this->getResourceType() == 'termcollection') {

            // Make sure
            ZfExtended_Factory
                ::get('editor_Models_Terminology_Models_CollectionAttributeDataType')
                ->onTermCollectionInsert($this->getId());
        }

        // Return value returned by save() call
        return $return;
    }
}
