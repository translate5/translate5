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

class editor_Models_TermCollection_TermEntryAttributes extends editor_Models_TermCollection_Attributes{
    protected $dbInstanceClass = 'editor_Models_Db_TermCollection_TermEntryAttributes';
    protected $validatorInstanceClass   = 'editor_Models_Validator_TermCollection_TermEntryAttributes';
    
    /***
     * Get all attributes for the given term entry (groupId)
     * @param string $groupId - original termEntry id from the tbx
     * @param array $collectionIds
     * @return array|NULL
     */
    public function getAttributesForTermEntry($groupId,$collectionIds){
        
        $cols=array(
                'LEK_term_entry_attributes.id AS attributeId',
                'LEK_term_entry_attributes.labelId as labelId',
                'LEK_term_entry_attributes.termEntryId AS termEntryId',
                'LEK_term_entry_attributes.parentId AS parentId',
                'LEK_term_entry_attributes.internalCount AS internalCount',
                'LEK_term_entry_attributes.language AS language',
                'LEK_term_entry_attributes.name AS name',
                'LEK_term_entry_attributes.attrType AS attrType',
                'LEK_term_entry_attributes.attrDataType AS attrDataType',
                'LEK_term_entry_attributes.attrTarget AS attrTarget',
                'LEK_term_entry_attributes.attrId AS attrId',
                'LEK_term_entry_attributes.attrLang AS attrLang',
                'LEK_term_entry_attributes.value AS attrValue',
                'LEK_term_entry_attributes.created AS attrCreated',
                'LEK_term_entry_attributes.updated AS attrUpdated',
                'LEK_term_entry.collectionId AS collectionId',
                new Zend_Db_Expr('"termEntryAttribute" as attributeOriginType')//this is needed as fixed value
        );
        
        $s=$this->db->select()
        ->from($this->db)
        ->join('LEK_term_entry', 'LEK_term_entry.id = LEK_term_entry_attributes.termEntryId',$cols)
        ->where('LEK_term_entry.groupId=?',$groupId)
        ->where('LEK_term_entry.collectionId IN(?)',$collectionIds);
        $s->setIntegrityCheck(false);
        $rows=$this->db->fetchAll($s)->toArray();
        if(!empty($rows)){
            return $rows;
        }
        return null;
    }
}