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
 * @method void setId() setId(int $id)
 * 
 * @method integer getLanguageResourceId getLanguageResourceId()
 * @method void setLanguageResourceId() setLanguageResourceId(int $languageResourceId)
 * 
 * @method integer getCategoryId() getCategoryId()
 * @method void setCategoryId() setCategoryId(int $categoryId)
 * 
 */
class editor_Models_LanguageResources_CategoryAssoc extends ZfExtended_Models_Entity_Abstract {
    
    protected $dbInstanceClass = 'editor_Models_Db_LanguageResources_CategoryAssoc';
    protected $validatorInstanceClass = 'editor_Models_Validator_LanguageResources_CategoryAssoc';
    
    /***
     * Save category assocs from the request parameters for the given language resource.
     * @param mixed $data
     */
    public function saveAssocRequest($data){
        if (!$this->checkUpdateSaveData($data)) {
            return;
        }
        if (empty($data['categories'])) {
            // when categories are empty, there is nothing to be saved.
            return;
        }
        $categories = json_decode($data['categories']);
        if (json_last_error() != JSON_ERROR_NONE) {
            $logger = Zend_Registry::get('logger');
            /* @var $logger ZfExtended_Logger */
            $logger->error('E1179', 'Save category assocs: categories could not be JSON-decoded with message: {msg}', [
                'msg' => json_last_error_msg(),
                'data' => $data['categories']
            ]);
            return;
        }
        $this->addAssocs($categories, $data['id']);
    }
    
    /***
     * Add assoc record to the database
     * @param mixed $categories
     * @param int $languageResourceId
     */
    protected function addAssocs($categories, $languageResourceId){
        foreach ($categories as $categoryId){
            $model = ZfExtended_Factory::get('editor_Models_LanguageResources_CategoryAssoc');
            /* @var $model editor_Models_LanguageResources_CategoryAssoc */
            $model->setCategoryId($categoryId);
            $model->setLanguageResourceId($languageResourceId);
            $model->save();
        }
    }
    
    /***
     * Check if the update or save data is valid.
     *
     * @param mixed $data
     * @return boolean
     */
    private function checkUpdateSaveData(&$data){
        //convert the data to array if it is of object type
        if(is_object($data)){
            $data = json_decode(json_encode($data), true);
        }
        return isset($data['categories']) && isset($data['id']);
    }
    
    /***
     * Get all assocs by $languageResourceId (languageResourceId).
     * If no $languageResourceId is provided, all assoc will be loaded
     * @param int $languageResourceId
     * @return array
     */
    public function loadByLanguageResourceId($languageResourceId=null){
        $s=$this->db->select();
        if($languageResourceId){
            $s->where('languageResourceId=?',$languageResourceId);
        }
        return $this->db->fetchAll($s)->toArray();
    }
    
    /***
     * Load category assoc grouped by language resource id.
     * @return array[]
     */
    public function loadCategoryIdsGrouped() {
        $assocs=$this->loadByLanguageResourceId();
        $retval=[];
        foreach ($assocs as $assoc){
            if(!isset($retval[$assoc['languageResourceId']])){
                $retval[$assoc['languageResourceId']]=[];
            }
            array_push($retval[$assoc['languageResourceId']],$assoc);
        }
        return $retval;
    }
}

