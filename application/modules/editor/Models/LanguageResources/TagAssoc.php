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
 * @method void setId() setId(int $id)
 * 
 * @method integer getLanguageResourceId getLanguageResourceId()
 * @method void setLanguageResourceId() setLanguageResourceId(int $languageResourceId)
 * 
 * @method integer getTagId() getTagId()
 * @method void setTagId() setTagId(int $tagId)
 * 
 */
class editor_Models_LanguageResources_TagAssoc extends ZfExtended_Models_Entity_Abstract {
    
    protected $dbInstanceClass = 'editor_Models_Db_LanguageResources_TagAssoc';
    protected $validatorInstanceClass = 'editor_Models_Validator_LanguageResources_TagAssoc';
    
    /***
     * Save tag assocs from the request parameters for the given language resource.
     * @param mixed $data
     */
    public function saveAssocRequest($data){
        if(!$this->checkUpdateSaveData($data)){
            return;
        }
        $tags = json_decode($data['tags']);
        $this->addAssocs($tags, $data['id']);
    }
    
    /***
     * Add assoc record to the database
     * @param mixed $tags
     * @param int $languageResourceId
     */
    protected function addAssocs($tags, $languageResourceId){
        foreach ($tags as $tagId){
            $model = ZfExtended_Factory::get('editor_Models_LanguageResources_TagAssoc');
            /* @var $model editor_Models_LanguageResources_TagAssoc */
            $model->setTagId($tagId);
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
        return isset($data['tags']) && isset($data['id']);
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
     * Load tag assoc grouped by language resource id.
     * @return array[]
     */
    public function loadTagIdsGrouped() {
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

