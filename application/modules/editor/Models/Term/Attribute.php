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
 * @method integer getId() getId()
 * @method void setId() setId(integer $id)
 * @method integer getLabelId() getLabelId()
 * @method void setLabelId() setLabelId(integer $labelId)
 * @method integer getCollectionId() getCollectionId()
 * @method void setCollectionId() setCollectionId(integer $collectionId)
 * @method integer getTermId() getTermId()
 * @method void setTermId() setTermId(integer $termId)
 * @method integer getParentId() getParentId()
 * @method void setParentId() setParentId(integer $parentId)
 * @method integer getInternalCount() getInternalCount()
 * @method void setInternalCount() setInternalCount(integer $internalCount)
 * @method integer getTermEntryId() getTermEntryId()
 * @method void setTermEntryId() setTermEntryId(integer $termEntryId)
 * @method string getLanguage() getLanguage()
 * @method void setLanguage() setLanguage(string $language)
 * @method string getName() getName()
 * @method void setName() setName(string $name)
 * @method string getAttrType() getAttrType()
 * @method void setAttrType() setAttrType(string $attrType)
 * @method string getAttrDataType() getAttrDataType()
 * @method void setAttrDataType() setAttrDataType(string $attrDataType)
 * @method string getAttrTarget() getAttrTarget()
 * @method void setAttrTarget() setAttrTarget(string $attrTarget)
 * @method string getAttrId() getAttrId()
 * @method void setAttrId() setAttrId(string $attrId)
 * @method string getAttrLang() getAttrLang()
 * @method void setAttrLang() setAttrLang(string $attrLang)
 * @method string getValue() getValue()
 * @method void setValue() setValue(string $value)
 * @method string getHistoryCreated() getHistoryCreated()
 * @method void setHistoryCreated() setHistoryCreated(string $created)
 * @method string getCreated() getCreated()
 * @method void setCreated() setCreated(string $created)
 * @method string getUpdated() getUpdated()
 * @method void setUpdated() setUpdated(string $updated)
 * @method string getUserGuid() getUserGuid()
 * @method void setUserGuid() setUserGuid(string $userGuid)
 * @method string getUserName() getUserName()
 * @method void setUserName() setUserName(string $userName)
 * @method string getProcessStatus() getProcessStatus()
 * @method void setProcessStatus() setProcessStatus(string $processStatus)
 */
class editor_Models_Term_Attribute extends ZfExtended_Models_Entity_Abstract {
    const ATTR_LEVEL_ENTRY = 'termEntry';
    const ATTR_LEVEL_LANGSET = 'langSet';
    const ATTR_LEVEL_TERM = 'term';
    
    protected $dbInstanceClass = 'editor_Models_Db_Term_Attribute';
    protected $validatorInstanceClass   = 'editor_Models_Validator_Term_Attribute';
    
    /***
     * Attribute fields which are not updatable
     * @var array
     */
    public $unupdatebleField = [
        'transac'
    ];
    
    /***
     * Available attrType for transac group transac attribute 
     * @var array
     */
    public static $trasacGroupTypes=[
        'creation',
        'origination',//Info:in some tbx files, the value orgination is used for representing creations
        'modification'
    ];
    
    /**
     * creates a new, unsaved term attribute history entity
     * @return editor_Models_Term_AttributeHistory
     */
    public function getNewHistoryEntity() {
        $history = ZfExtended_Factory::get('editor_Models_Term_AttributeHistory');
        /* @var $history editor_Models_Term_AttributeHistory */
        $history->setAttributeId($this->getId());
        $history->setHistoryCreated(NOW_ISO);
        
        $fields = $history->getFieldsToUpdate();
        foreach ($fields as $field) {
            $history->__call('set' . ucfirst($field), array($this->get($field)));
        }
        return $history;
    }

    /**
     * returns true if the attribute is proposable according to its values. 
     * Additional ACL checks must be done outside (in the controllers)
     * @param string $name optional, if both parameters are empty the values from $this are used 
     * @param string $type optional, if both parameters are empty the values from $this are used 
     */
    public function isProposable($name = null, $type = null) {
        if(empty($name) && empty($type)) {
            $name = $this->getName();
            $type = $this->getAttrType();
        }
        return !($name == 'date'
            || $name=='termNote' && $type=='processStatus'
            || $name=='transacNote' && ($this->isResponsablePersonAttribute($type))
            || $name=='transac' && ($type=='creation' || $type=='origination')
            || $name=='transac' && $type=='modification');
    }
    
    /***
     * Is the user allowed for attribute proposal
     * 
     * @return boolean
     */
    public function isProposableAllowed(){
        $user=ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        return $user->hasRole('termProposer');
    }
    
    /**
     * Loads an attribute for the given term
     */
    public function loadByTermAndName(editor_Models_Term $term, $name, $level = self::ATTR_LEVEL_TERM) {
        $s = $this->db->select()->where('collectionId = ?', $term->getCollectionId());
        $s->where('termEntryId = ?', $term->getTermEntryId());
        if($level == self::ATTR_LEVEL_LANGSET || $level == self::ATTR_LEVEL_TERM) {
            $lang = ZfExtended_Factory::get('editor_Models_Languages');
            /* @var $lang editor_Models_Languages */
            $lang->loadById($term->getLanguage());
            $s->where('language = ?',strtolower($lang->getRfc5646()));
        }
        else {
            $s->where('language is null');
        }
        
        if($level == self::ATTR_LEVEL_TERM) {
            $s->where('termId = ?', $term->getId());
        }
        else {
            $s->where('termId is null');
        }
        $s->where('name = ?', $name);
        
        $row = $this->db->fetchRow($s);
        if (!$row) {
            $this->notFound(__CLASS__ . '#termAndName', $term->getId().'; '.$name);
        }
        //load implies loading one Row, so use only the first row
        $this->row = $row;
    }
    
    /***
     * Save or update an attribute
     *
     * @return mixed|array
     */
    public function saveOrUpdate(){
        $s = $this->db->select()
        ->from(['a'=>'LEK_term_attributes']);
        $toSave=$this->row->toArray();
        $notCheckField=array(
            'id',
            'created',
            'updated',
            'userGuid',
            'userName',
            'processStatus'
        );
        
        //check if the field is unupdatable
        $isUnupdatable=in_array($toSave['name'], $this->unupdatebleField);
        foreach ($toSave as $key=>$value){
            //if notcheck column
            if(in_array($key, $notCheckField)){
                continue;
            }
            
            //ignore the value check in all updatable column
            //the value check is needed only in the unupdatable columns
            if(!$isUnupdatable && $key==='value'){
                continue;
            }
            
            //if the value is null
            if($value==null){
                $s->where('`a`.'.$key.' IS NULL');
                continue;
            }
            
            $s->where('`a`.'.$key.'=?',$value);
        }
        
        //check if the field exist
        $checkRow=$this->db->fetchRow($s);
        if(empty($checkRow)){
            $this->setUpdated(date("Y-m-d H:i:s"));
            //the field does not exist
            return $this->save();
        }
        
        //the field exist, set the id it is needed for parentId
        $this->setId($checkRow['id']);
        if($isUnupdatable){
            return $checkRow['id'];
        }
        //the same values, ignore
        if($checkRow['value']===$toSave['value']){
            $this->load($checkRow['id']);
            $this->setUpdated(date("Y-m-d H:i:s"));
            return $this->save();
        }
        
        $proposal=ZfExtended_Factory::get('editor_Models_Term_AttributeProposal');
        /* @var $proposal editor_Models_Term_AttributeProposal */
        if($proposal->isProposal($checkRow['id'], $toSave['value'])){
            $proposal->removeAttributeProposal($checkRow['id'], $toSave['value']);
        }
        
        //new value is found, update the attribute
        //load the record
        $this->load($checkRow['id']);
        $this->setValue($toSave['value']);
        $this->setUpdated(date("Y-m-d H:i:s"));
        return $this->save();
    }
    
    /***
     * Get all attributes for the given term entry (groupId)
     * @param string $termEntryId
     * @param array $collectionIds
     * @return array|NULL
     */
    public function getAttributesForTermEntry($termEntryId,$collectionIds){
        $cols = array(
            'LEK_term_attributes.id AS attributeId',
            'LEK_term_attributes.labelId as labelId',
            'LEK_term_attributes.termEntryId AS termEntryId',
            'LEK_term_attributes.parentId AS parentId',
            'LEK_term_attributes.internalCount AS internalCount',
            'LEK_term_attributes.language AS language',
            'LEK_term_attributes.name AS name',
            'LEK_term_attributes.attrType AS attrType',
            'LEK_term_attributes.attrDataType AS attrDataType',
            'LEK_term_attributes.attrTarget AS attrTarget',
            'LEK_term_attributes.attrId AS attrId',
            'LEK_term_attributes.attrLang AS attrLang',
            'LEK_term_attributes.value AS attrValue',
            'LEK_term_attributes.created AS attrCreated',
            'LEK_term_attributes.updated AS attrUpdated',
            'LEK_term_entry.collectionId AS collectionId',
            new Zend_Db_Expr('"termEntryAttribute" as attributeOriginType')//this is needed as fixed value
        );
        
        $s=$this->db->select()
        ->from($this->db,[])
        ->join('LEK_term_entry', 'LEK_term_entry.id = LEK_term_attributes.termEntryId',$cols)
        ->joinLeft('LEK_term_attributes_label', 'LEK_term_attributes_label.id = LEK_term_attributes.labelId',['LEK_term_attributes_label.labelText as headerText']);

        if($this->isProposableAllowed()){
            $s->joinLeft('LEK_term_attribute_proposal', 'LEK_term_attribute_proposal.attributeId = LEK_term_attributes.id',[
                'LEK_term_attribute_proposal.value as proposalAttributeValue',
                'LEK_term_attribute_proposal.id as proposalAttributelId',
            ]);
        }
        
        $s->where('LEK_term_attributes.termId is null')
        ->where('LEK_term_entry.id=?',$termEntryId)
        ->where('LEK_term_entry.collectionId IN(?)',$collectionIds);
        $s->setIntegrityCheck(false);
        $rows=$this->db->fetchAll($s)->toArray();
        if(empty($rows)){
            return null;
        }
        $mapProposal = function($item) {
            $item['proposable']=$this->isProposable($item['name'],$item['attrType']);
            $item['proposal']=null;
            if(!isset($item['proposalAttributelId'])){
                unset($item['proposalAttributelId']);
                if(!isset($item['proposalAttributeValue'])){
                    unset($item['proposalAttributeValue']);
                }
                return $item;
            }
            $obj=new stdClass();
            $obj->id=$item['proposalAttributelId'];
            $obj->value=$item['proposalAttributeValue'];
            $item['proposal']=$obj;
            return $item;
        };
        $rows=array_map($mapProposal, $rows);
        return $this->createChildTree($rows);
    }
    
    /***
     * Update the term transac group attributes from the proposal attributes
     * @param editor_Models_Term $term
     * @param editor_Models_Term_Proposal $proposal
     * @return boolean
     */
    public function updateTermTransacGroupFromProposal(editor_Models_Term $term,editor_Models_Term_Proposal $proposal){
        $attributes=$this->getTransacGroup($term,self::$trasacGroupTypes);
        if(!empty($attributes)){
            $s=$this->db->select()
            ->where('parentId IN(?)',array_column($attributes,'id'));
            $child=$this->db->fetchAll($s)->toArray();
            $attributes=array_merge($attributes,$child);
        }
        if(empty($attributes)){
            return false;
        }
        foreach ($attributes as $rec){
            $attribute=ZfExtended_Factory::get('editor_Models_Term_Attribute');
            /* @var $attribute editor_Models_Term_Attribute */
            $attribute->load($rec['id']);
            if($attribute->isDateAttribute()){
                $attribute->setValue(strtotime($proposal->getCreated()));
            }
            if($attribute->isResponsablePersonAttribute()){
                $attribute->setValue($proposal->getUserName());
            }
            $attribute->setUserGuid($proposal->getUserGuid());
            $attribute->setUserName($proposal->getUserName());
            $attribute->save();
            
        }
        return true;
    }
    /***
     * Update term processStatus attribute with the given value
     * @param editor_Models_Term $term
     * @param string $processStatus
     * @return boolean
     */
    public function updateTermProcessStatus(editor_Models_Term $term,string $processStatus){
        return $this->db->update([
            'value' =>$processStatus,
            'processStatus' => $processStatus
        ], [
            'termId=?' => $term->getId(),
            'name=?' =>'termNote',
            'attrType=?' => 'processStatus'
        ])>0;
    }
    
    /***
     * Get transac attribute for the entity and type
     * 
     * @param editor_Models_Term|editor_Models_TermCollection_TermEntry $entity
     * @param array $types
     * @return array
     */
    public function getTransacGroup($entity,array $types){
        $s=$this->db->select();
        if($entity instanceof editor_Models_Term){
            $s->where('termId=?',$entity->getId());
        }
        if($entity instanceof editor_Models_TermCollection_TermEntry){
            $s->where('termEntryId=?',$entity->getId());
            $s->where('termId IS NULL');
        }
        $s->where('name="transac"')
        ->where('attrType IN(?)',$types);
        return $this->db->fetchAll($s)->toArray();
    }
    
    /***
     * Handle transac attributes group. If no transac group attributes exist for the entity, new one will be created.
     * 
     * @param editor_Models_Term|editor_Models_TermCollection_TermEntry $entity
     */
    public function handleTransacGroup($entity){
        if($entity->getId()==null){
            return false;
        }
        $ret=$this->getTransacGroup($entity, ['modification']);
        //if the transac group exist, do nothing
        if(!empty($ret)){
            return false;
        }
        //the transacgroups are not existing, create new
        $this->createTransacGroup($entity,'creation');
        $this->createTransacGroup($entity,'modification');
        return true;
    }
    
    /***
     * Create transac group attributes with its values. The type can be creation or modification
     * Depending on what kind of entity is passed, the appropriate attribute will be created(term attribute or term entry attribute)
     * 
     * @param editor_Models_Term|editor_Models_TermCollection_TermEntry $entity
     * @param string $type
     */
    public function createTransacGroup($entity,string $type) {
        
        $labelModel=ZfExtended_Factory::get('editor_Models_TermCollection_TermAttributesLabel');
        /* @var $labelModel editor_Models_TermCollection_TermAttributesLabel */
        $labelArray=$labelModel->loadAll();
        
        $languagesModel=ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $languagesModel editor_Models_Languages */
        $languagesArray=$languagesModel->loadAllKeyValueCustom('id','rfc5646');
        
        $user = new Zend_Session_Namespace('user');
        $fullName = $user->data->firstName.' '.$user->data->surName;
        
        $transacLabelId=null;
        $dateLabelId=null;
        $transacNoteLabelId=null;
        
        foreach ($labelArray as $lbl){
            if($lbl['label']=='transac' && $lbl['type']==$type){
                $transacLabelId=$lbl['id'];
            }
            if($lbl['label']=='date'){
                $dateLabelId=$lbl['id'];
            }
            if($lbl['label']=='transacNote' && $this->isResponsablePersonAttribute($lbl['type'])){
                $transacNoteLabelId=$lbl['id'];
            }
        }
        
        //transac
        $this->init();
        $this->setLabelId($transacLabelId);
        $this->setCollectionId($entity->getCollectionId());
        //if the entity is term, set term attribute
        if($entity instanceof editor_Models_Term){
            $this->setTermId($entity->getId());
            $this->setLanguage($languagesArray[$entity->getLanguage()]);
            $this->setAttrLang($languagesArray[$entity->getLanguage()]);
        }
        //if the entity is termentry set term entry attribute
        if($entity instanceof editor_Models_TermCollection_TermEntry){
            $this->setTermEntryId($entity->getId());
        }
        
        $this->setName('transac');
        $this->setAttrType($type);
        $this->setValue($type);
        $this->setUserGuid($user->data->userGuid);
        $this->setUserName($fullName);
        //save the transac attribute
        $parentId=$this->save();
        
        //date
        $this->init();
        $this->setLabelId($dateLabelId);
        $this->setCollectionId($entity->getCollectionId());
        
        if($entity instanceof editor_Models_Term){
            $this->setTermId($entity->getId());
            $this->setLanguage($languagesArray[$entity->getLanguage()]);
            $this->setAttrLang($languagesArray[$entity->getLanguage()]);
        }
        
        if($entity instanceof editor_Models_TermCollection_TermEntry){
            $this->setTermEntryId($entity->getId());
        }
        
        $this->setParentId($parentId);
        $this->setName('date');
        $this->setValue(time());
        $this->setUserGuid($user->data->userGuid);
        $this->setUserName($fullName);
        //save the date attribute
        $this->save();
        
        //responsiblePerson
        $this->init();
        $this->setLabelId($transacNoteLabelId);
        $this->setCollectionId($entity->getCollectionId());
        
        if($entity instanceof editor_Models_Term){
            $this->setTermId($entity->getId());
            $this->setLanguage($languagesArray[$entity->getLanguage()]);
            $this->setAttrLang($languagesArray[$entity->getLanguage()]);
        }
        if($entity instanceof editor_Models_TermCollection_TermEntry){
            $this->setTermEntryId($entity->getId());
        }
        $this->setParentId($parentId);
        $this->setName('transacNote');
        $this->setAttrType('responsiblePerson');
        $this->setValue($fullName);
        $this->setUserGuid($user->data->userGuid);
        $this->setUserName($fullName);
        //save the responsible person attribute
        $this->save();
    }
    
    /***
     * Group the attributes by parent-child
     *
     * @param array $list
     * @param int $parentId
     * @return array
     */
    public function createChildTree(array $list, $parentId = 0){
        if(count($list)==1){
            return $list;
        }
        $tree = array();
        foreach ($list as $element) {
            if ($element['parentId'] == $parentId) {
                $children = $this->createChildTree($list, $element['attributeId']);
                if ($children) {
                    $element['children'] = $children;
                }else{
                    $element['children'] = [];
                }
                $tree[] = $element;
            }
        }
        return $tree;
    }
    
    
    /***
     * Update term modification attribute group with the term proposal values.
     * The modification group date and editor will be set as term proposal date an term proposal editor.
     * 
     * @param array $attributes
     * @param array $termProposal
     */
    public function updateModificationGroupDate(array $attributes,array $termProposal){
        if(empty($attributes) || empty($termProposal) || empty($termProposal['created']) || empty($termProposal['userName'])){
            return $attributes;
        }
        
        //foreach term attribute check, find the transac modification attribute
        foreach ($attributes as &$attribute){
            
            if(empty($attribute['children'])){
                continue;
            }
            
            //ignore non modification tags
            if($attribute['name']!='transac' || $attribute['attrType']!='modification'){
                continue;
            }
            
            foreach ($attribute['children'] as &$child){
                if($child['name']=='date'){
                    //convert the date to unix timestamp
                    $child['attrValue']=strtotime($termProposal['created']);
                }
                if($child['name']=='transacNote' && $this->isResponsablePersonAttribute($child['attrType'])){
                    $child['attrValue']=$termProposal['userName'];
                }
            }
        }
        return $attributes;
    }
    
    /***
     * Check if for the current term there is a processStatus attribute. When there is no one, create it.
     * @param int $termId
     * @return NULL|mixed|array
     */
    public function checkOrCreateProcessStatus(int $termId) {
        $s=$this->db->select()
        ->where('termId=?',$termId)
        ->where('name="termNote"')
        ->where('attrType="processStatus"');
        
        $result=$this->db->fetchAll($s)->toArray();
        if(count($result)>0){
            return null;
        }
        
        $term=ZfExtended_Factory::get('editor_Models_Term');
        /* @var $term editor_Models_Term */
        $term->load($termId);
        
        $language=ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $language editor_Models_Languages */
        
        $language->loadById($term->getLanguage());
        
        
        $this->setCollectionId($term->getCollectionId());
        $this->setTermId($term->getId());
        
        $this->setLanguage($language->getRfc5646());
        
        $this->setName('termNote');
        $this->setAttrType('processStatus');
            
        $label = ZfExtended_Factory::get('editor_Models_TermCollection_TermAttributesLabel');
        /* @var $label editor_Models_TermCollection_TermAttributesLabel */
        $label->loadOrCreate('termNote', 'processStatus');
        $this->setLabelId($label->getId());
        
        $this->setAttrLang($language->getRfc5646());
        
        $this->setUserGuid($term->getUserGuid());
        $this->setUserName($term->getUserName());
        $this->setProcessStatus($term->getProcessStatus());
        $this->setValue($term->getProcessStatus());
        
        return $this->save();
    }
    
    /***
     * Add comment attribute for given term
     * @param int $termId
     * @param string $termText
     * @return editor_Models_Term_Attribute
     */
    public function addTermComment(int $termId,string $termText){
        $term=ZfExtended_Factory::get('editor_Models_Term');
        /* @var $term editor_Models_Term */
        $term->load($termId);
        
        $lang = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $lang editor_Models_Languages */
        $lang->loadById($term->getLanguage());
        
        $label = ZfExtended_Factory::get('editor_Models_TermCollection_TermAttributesLabel');
        /* @var $label editor_Models_TermCollection_TermAttributesLabel */
        $label->loadOrCreate('note', null);
        
        $this->init([
            'name' => 'note',
            'created' => NOW_ISO,
            'internalCount' => 1,
            'collectionId' => $term->getCollectionId(),
            'termId'=>$term->getId(),
            'termEntryId' => $term->getTermEntryId(),
            'language' => strtolower($lang->getRfc5646()),
            'attrLang' => strtolower($lang->getRfc5646()),
            'labelId' => $label->getId(),
            'processStatus'=>editor_Models_Term::PROCESS_STATUS_UNPROCESSED
        ]);
        $this->setValue(trim($termText));
        $sessionUser = new Zend_Session_Namespace('user');
        $this->setUserGuid($sessionUser->data->userGuid);
        $this->setUserName($sessionUser->data->userName);
        $this->hasField('updated') && $this->setUpdated(NOW_ISO);
        $this->save();
        return $this;
    }
    
    /***
     * Check if the attribute is processStatus attribute
     * @return boolean
     */
    public function isProcessStatusAttribute(){
        return $this->getAttrType()=='processStatus' && $this->getName()=='termNote';
    }
    
    /***
     * Check if given AttrType is for responsable person
     * Info: the responsable person type is saved in with different values in some tbx files
     * @param string $type
     * @return boolean
     */
    public function isResponsablePersonAttribute(string $type=null){
        if(!empty($type)){
            return $type=='responsiblePerson' || $type=='responsibility';
        }
        if($this->getAttrType()!=null){
            return $this->getAttrType()=='responsiblePerson' || $this->getAttrType()=='responsibility';
        }
        return false;
    }
    
    /***
     * Check if it is date attribute. Can be checked via $name param, or if the attribute row is loaded via
     * $this->getName()
     * 
     * @param string $name
     * @return boolean
     */
    public function isDateAttribute(string $name=null) {
        if(!empty($name)){
            return $name=='date';
        }
        if($this->getName()!=null){
            return $this->getName()=='date';
        }
        return false;
    }
    
    public function getDataObject() {
        $result=parent::getDataObject();
        //TODO: for non proposal users, ignore the custom attribute values for the api
        //set the attribute origin type(when no termId is provided it is termEntry attribute otherwise term attribute)
        $result->attributeOriginType=empty($result->termId) ? 'termEntryAttribute' : 'termAttribute';
        $result->attributeId=$result->id;
        $result->proposable=$this->isProposableAllowed() && $this->isProposable($result->name,$result->attrType);
        $result->attrProcessStatus=$result->processStatus;//used by termporatl
        $result->attrValue=$result->value;//used by termporatl
        //get the attribute header for the label
        //the header value will be translated
        $result->headerText=$result->name.' '.$result->attrType;
        try {
            $label=ZfExtended_Factory::get('editor_Models_TermCollection_TermAttributesLabel');
            /* @var $label editor_Models_TermCollection_TermAttributesLabel */
            $label->load($result->labelId);
            if($label->getLabelText()!=null && $label->getLabelText()!=''){
                $result->headerText=$label->getLabelText();
            }
        } catch (Exception $e) {
            //just catch if unfound is thrown
        }
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        //translate the header text
        $result->headerText=$translate->_($result->headerText);
        return $result;
    }
}