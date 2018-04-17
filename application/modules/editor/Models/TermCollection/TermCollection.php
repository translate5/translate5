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

class editor_Models_TermCollection_TermCollection extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_TermCollection_TermCollection';
    protected $validatorInstanceClass   = 'editor_Models_Validator_TermCollection_TermCollection';
    
    /***
     * Import the tbx files in the term collection
     * 
     * @param array $filePath
     * @param integer $collectionId
     * @param integer $customerId
     * @return void|boolean
     */
    public function importTbx(array $filePath,integer $collectionId,integer $customerId){
        $import=ZfExtended_Factory::get('editor_Models_Import_TermListParser_Tbx');
        /* @var $import editor_Models_Import_TermListParser_Tbx */
        $import->mergeTerms=true;
        $import->customerId=$customerId;
        return $import->parseTbxFile($filePath,$collectionId);
    }
    
    /***
     * Get all collection associated with the task
     * 
     * @param guid $taskGuid
     * @return array
     */
    public function getCollectionsForTask($taskGuid){
        $s=$this->db->select()
        ->from('LEK_term_collection_taskassoc')
        ->where('taskGuid=?',$taskGuid);
        return $this->db->fetchRow($s)->toArray();
    }
    
    
    /***
     * Get the attribute count for the collection
     * The return array will be in format: 
     *  [
     *      'termsCount'=>number,
     *      'termsAtributeCount'=>number,
     *      'termsEntryAtributeCount'=>number,
     *  ]
     * @param unknown $collectionId
     * @return array
     */
    public function getAttributesCountForCollection($collectionId){
        $s=$this->db->select()
        ->setIntegrityCheck(false)
        ->from(array('tc' => 'LEK_term_collection'), array('id'))
        ->join(array('t' => 'LEK_terms'),'tc.id=t.collectionId', array('count(DISTINCT t.id) as termsCount'))
        ->join(array('ta' => 'LEK_term_attributes'),'tc.id=ta.collectionId', array('count(DISTINCT ta.id) as termsAtributeCount'))
        ->join(array('tea' => 'LEK_term_entry_attributes'),'tc.id=tea.collectionId', array('count(DISTINCT tea.id) as termsEntryAtributeCount'))
        ->where('tc.id =?',$collectionId);
        return $this->db->fetchRow($s)->toArray();
    }
}