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

class editor_Models_TermCollection_TermEntryAttributes extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_TermCollection_TermEntryAttributes';
    protected $validatorInstanceClass   = 'editor_Models_Validator_TermCollection_TermEntryAttributes';
    

    public $unupdatebleField=array(
            'transac'
    );
    
    public function saveOrUpdate(){
        $s = $this->db->select();
        $toSave=$this->row->toArray();
        $notCheckField=array(
                'id',
                'value'
        );
        
        //check if the field is unupdatable
        //transac field with value creation and modification are unupdatable
        $isUnupdatable=in_array($toSave['name'], $this->unupdatebleField);
        foreach ($toSave as $key=>$value){
            
            if(in_array($key, $notCheckField)){
                continue;
            }
            //if($key=='value'){
            //    $comparator=$isUnupdatable ? ($key.'=?') : ($key.'!=?');
            //    $s->where($comparator,$value);
            //    continue;
            //}
            if($value==null){
                $s->where($key.' IS NULL');
                continue;
            }
            
            $s->where($key.'=?',$value);
        }
        //check if the field exist, save if not update if yes
        $checkRow=$this->db->fetchRow($s);
        if(empty($checkRow)){
            $this->save();
            return ;
        }
        
        if($checkRow['value']===$toSave['value']){
            $this->setId($checkRow['id']);
            return;
        }
        
        //load the record
        $this->load($checkRow['id']);
        //update the value with the new one
        $this->setValue($toSave['value']);
        $this->save();
    }
    
    public function saveOrUpdateTransac(){
        $s = $this->db->select();
        $toSave=$this->row->toArray();
        $notCheckField=array(
                'id'
        );
        foreach ($toSave as $key=>$value){
            if(in_array($key, $notCheckField)){
                continue;
            }
            if($value==null){
                $s->where($key.' IS NULL');
                continue;
            }
            
            $s->where($key.'=?',$value);
        }
        //save the field if does not exist
        $checkRow=$this->db->fetchRow($s);
        if(empty($checkRow)){
            $this->save();
            return ;
        }
        $this->setId($checkRow['id']);
    }
}