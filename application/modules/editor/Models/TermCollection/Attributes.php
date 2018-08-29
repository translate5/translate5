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

abstract class editor_Models_TermCollection_Attributes extends ZfExtended_Models_Entity_Abstract {
    
    
    /***
     * Attribute fields which are not updatable
     * @var array
     */
    public $unupdatebleField=array(
            'transac'
    );
    
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
}