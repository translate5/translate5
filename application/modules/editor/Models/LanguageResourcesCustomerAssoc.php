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
 * 
 * @method integer getLanguageResourceId getLanguageResourceId()
 * @method void setLanguageResourceId() setLanguageResourceId(integer $languageResourceId)
 * 
 * @method integer getCustomerId() getCustomerId()
 * @method void setCustomerId() setCustomerId(integer $customerId)
 * 
 * @method integer getDefaultResource() getDefaultResource()
 * @method void setDefaultResource() setDefaultResource(integer $defaultResource)
 * 
 */
class editor_Models_LanguageResourcesCustomerAssoc extends ZfExtended_Models_Entity_Abstract {
    
    protected $dbInstanceClass = 'editor_Models_Db_LanguageResourcesCustomerAssoc';
    protected $validatorInstanceClass = 'editor_Models_Validator_LanguageResourcesCustomerAssoc';
    
    /***
     * Save customer assoc for the given language resource
     * @param mixed $data
     */
    public function saveAssoc($data){
        if(is_object($data)){
            $data=json_decode(json_encode($data), true);
        }
        if(!isset($data['resourcesCustomersHidden']) || !isset($data['id'])){
            return;
        }
        //the data is in comma separated values
        $customers=explode(',',$data['resourcesCustomersHidden']);
        foreach ($customers as $customer){
            if(empty($customer)){
                continue;
            }
            $model=ZfExtended_Factory::get('editor_Models_LanguageResourcesCustomerAssoc');
            /* @var $model editor_Models_LanguageResourcesCustomerAssoc */
            $model->setCustomerId($customer);
            $model->setLanguageResourceId($data['id']);
            $model->save();
        }
    }
    
    /***
     * Update customer assoc.
     * @param mixed $data
     */
    public function updateAssoc($data){
        if(is_object($data)){
            $data=json_decode(json_encode($data), true);
        }
        
        //remove old assocs for the curen languageResourceId
        $deleteParams=array();
        $deleteParams['languageResourceId IN (?)'] = $data['id'];
        $this->db->delete($deleteParams);
        
        //save the new data
        $this->saveAssoc($data);
    }
    
    /***
     * Get all assocs by $languageResourceId (languageResourceId/tmmtid).
     * If no $languageResourceId is provided, all assoc will be loaded
     * @param integer $languageResourceId
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
     * Get all assocs by $customerIds
     * If no $customerIds is provided, all assoc will be loaded
     * @param array $customerIds
     * @return array
     */
    public function loadByCustomerIds($customerIds=array()){
        $s=$this->db->select();
        if(!empty($customerIds)){
            $s->where('customerId IN(?)',$customerIds);
        }
        return $this->db->fetchAll($s)->toArray();
    }
    
    /***
     * Get all customers for $languageResourceId (languageResourceId/tmmtid)
     * @param integer $languageResourceId
     * @return array
     */
    public function loadCustomerIds($languageResourceId) {
        $resources=$this->loadByLanguageResourceId($languageResourceId);
        $retval=[];
        foreach ($resources as $res){
            $retval[]=$res['customerId'];
        }
        return $retval;
    }
    
    /***
     * Load customer assoc grouped by language resource id.
     * @return array[]
     */
    public function loadCustomerIdsGrouped() {
        $assocs=$this->loadByLanguageResourceId();
        $retval=[];
        foreach ($assocs as $assoc){
            if(!isset($retval[$assoc['languageResourceId']])){
                $retval[$assoc['languageResourceId']]=[];
            }
            array_push($retval[$assoc['languageResourceId']], $assoc['customerId']);
        }
        return $retval;
    }
}

