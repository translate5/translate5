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
    protected $dbInstanceClass = 'editor_Models_Db_Term_Attribute';
    protected $validatorInstanceClass   = 'editor_Models_Validator_Term_Attribute';
    
    /***
     * Attribute fields which are not updatable
     * @var array
     */
    public $unupdatebleField = [
        'transac'
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
    
    /***
     * Save or update an attribute
     *
     * @return mixed|array
     */
    public function saveOrUpdate(){
        $s = $this->db->select();
        $toSave=$this->row->toArray();
        $notCheckField=array(
            'id',
            'created',
            'updated'
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
                $s->where($key.' IS NULL');
                continue;
            }
            
            $s->where($key.'=?',$value);
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
        
        //new value is found, update the attribute
        //load the record
        $this->load($checkRow['id']);
        $this->setValue($toSave['value']);
        $this->setUpdated(date("Y-m-d H:i:s"));
        return $this->save();
    }
    
    /***
     * Get all attributes for the given term entry (groupId)
     * @param string $groupId - original termEntry id from the tbx
     * @param array $collectionIds
     * @return array|NULL
     */
    public function getAttributesForTermEntry($groupId,$collectionIds){
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
        
//FIXME testen ob hier die korrekten Attribute geladen werden
        $s=$this->db->select()
        ->from($this->db,[])
        ->join('LEK_term_entry', 'LEK_term_entry.id = LEK_term_attributes.termEntryId',$cols)
        ->joinLeft('LEK_term_attributes_label', 'LEK_term_attributes_label.id = LEK_term_attributes.labelId',['LEK_term_attributes_label.labelText as headerText'])
        ->where('LEK_term_attributes.termId is null')
        ->where('LEK_term_entry.groupId=?',$groupId)
        ->where('LEK_term_entry.collectionId IN(?)',$collectionIds);
        $s->setIntegrityCheck(false);
        $rows=$this->db->fetchAll($s)->toArray();
        
        $rows=$this->createChildTree($rows);
        if(!empty($rows)){
            return $rows;
        }
        return null;
    }
    
    /***
     * Update modification transac group attributes.
     * When the attribute group does not exist, transac creation and transac modification will be created.
     *
     * @param editor_Models_Term $term
     */
    public function updateModificationGroupAttributes(editor_Models_Term $term){
        $s=$this->db->select()
        ->where('termId=?',$term->getId())
        ->where('name="transac"')
        ->where('attrType="modification"');
        $ret=$this->db->fetchAll($s)->toArray();
        $user = new Zend_Session_Namespace('user');
        $fullName = $user->data->firstName.' '.$user->data->surName;
        if(!empty($ret)){
            $ret=$ret[0];
            $this->db->update(['updated'=>null],['id=?'=>$ret['id']]);
            $this->db->update(['updated'=>null,'value'=>time()],['parentId=?'=>$ret['id'],'name="date"']);
            $this->db->update(['updated'=>null,'value'=>$fullName],['parentId=?'=>$ret['id'],'name="transacNote"','attrType="responsiblePerson"']);
            return;
        }
        //the transacgroups are not existing, create new
        $this->createTransacGroup($term,'creation');
        $this->createTransacGroup($term,'modification');
    }
    
    /***
     * Create transac group attributes with its values. The type can be creation or modification
     * @param editor_Models_Term $term
     * @param string $type
     */
    protected function createTransacGroup(editor_Models_Term $term,string $type) {
        
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
            if($lbl['label']=='transacNote' && $lbl['type']=='responsiblePerson'){
                $transacNoteLabelId=$lbl['id'];
            }
        }
        
        //transac
        $this->init();
        $this->setLabelId($transacLabelId);
        $this->setCollectionId($term->getCollectionId());
        $this->setTermId($term->getId());
        $this->setLanguage($languagesArray[$term->getLanguage()]);
        $this->setName('transac');
        $this->setAttrType($type);
        $this->setAttrLang($languagesArray[$term->getLanguage()]);
        $this->setValue($type);
        $parentId=$this->save();
        
        //date
        $this->init();
        $this->setLabelId($dateLabelId);
        $this->setCollectionId($term->getCollectionId());
        $this->setTermId($term->getId());
        $this->setParentId($parentId);
        $this->setLanguage($languagesArray[$term->getLanguage()]);
        $this->setName('date');
        $this->setAttrLang($languagesArray[$term->getLanguage()]);
        $this->setValue(time());
        $this->save();
        
        //responsiblePerson
        $this->init();
        $this->setLabelId($transacNoteLabelId);
        $this->setCollectionId($term->getCollectionId());
        $this->setTermId($term->getId());
        $this->setParentId($parentId);
        $this->setLanguage($languagesArray[$term->getLanguage()]);
        $this->setName('transacNote');
        $this->setAttrType('responsiblePerson');
        $this->setAttrLang($languagesArray[$term->getLanguage()]);
        $this->setValue($fullName);
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
}