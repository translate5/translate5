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

class editor_Models_TermCollection_TermAttributes extends editor_Models_TermCollection_Attributes{
    protected $dbInstanceClass = 'editor_Models_Db_TermCollection_TermAttributes';
    protected $validatorInstanceClass   = 'editor_Models_Validator_TermCollection_TermAttributes';
    
    
    /***
     * Update the term transacGrp attributes.
     * TransacGrp layout example:
     * 
     * <transacGrp>
     *  <transac>modification</transac>
     *  <date>2018-01-12</date>
     *  <transacNote type="responsiblePerson">Aleksandar Mitrev</transacNote>
     * </transacGrp>
     * 
     */
    public function updateTransacGrp(int $termId,string $transac,string $date=null,string $transacNote=null){
        $s=$this->db->select()
        ->where('termId=?',$termId)
        ->where('name="transac"')
        ->where('attrType=?',$transac);
        $result=$this->db->fetchAll($s)->toArray();
        if(empty($result)){
            return;
        }
        
        foreach ($result as $res){
            
        }
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
    
}