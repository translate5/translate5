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
 * @method integer getCustomerId() getCustomerId()
 * @method void setCustomerId() setCustomerId(int $customerId)
 * 
 * @method integer getUseAsDefault() getUseAsDefault()
 * @method void setUseAsDefault() setUseAsDefault(int $useAsDefault)
 * 
 */
class editor_Models_LanguageResources_CustomerAssoc extends ZfExtended_Models_Entity_Abstract {
    
    protected $dbInstanceClass = 'editor_Models_Db_LanguageResources_CustomerAssoc';
    protected $validatorInstanceClass = 'editor_Models_Validator_LanguageResources_CustomerAssoc';
    
    /***
     * Save customer assoc from the request parametars for the given language resource.
     * A language resource that is saved must have at least one customer assigned
     * (if none is given, we use the defaultcustomer).
     * @param mixed $data
     */
    public function saveAssocRequest($data){
        if(!$this->checkUpdateSaveData($data)){
            return;
        }
        
        //the data is in json
        $customers=json_decode($data['resourcesCustomersHidden']);
        
        // Check if (at least one) customer is set and use the 'defaultcustomer' if not
        if (empty($customers)) {
            $customer = ZfExtended_Factory::get('editor_Models_Customer');
            /* @var $customer editor_Models_Customer */
            $customer->loadByDefaultCustomer();
            $customers[] = $customer->getId();
        }
        
        $this->addAssocs($customers, $data['id']);
    }
    
    /***
     * Update customer assoc from the request parametars.
     * @param mixed $data
     */
    public function updateAssocRequest($data){
        if(!$this->checkUpdateSaveData($data)){
            return;
        }
        //remove old assocs for the curen languageResourceId
        $deleteParams=array();
        $deleteParams['languageResourceId IN (?)'] = $data['id'];
        $this->db->delete($deleteParams);
        
        //save the new data
        $this->saveAssocRequest($data);
    }
    
    /***
     * Add assoc record to the database
     * @param mixed $customers
     * @param int $languageResourceId
     */
    public function addAssocs($customers,$languageResourceId){
        foreach ($customers as $customer){
            $model=ZfExtended_Factory::get('editor_Models_LanguageResources_CustomerAssoc');
            /* @var $model editor_Models_LanguageResources_CustomerAssoc */
            $model->setCustomerId(isset($customer->customerId) ? $customer->customerId : $customer);
            $model->setLanguageResourceId($languageResourceId);
            $model->setUseAsDefault(isset($customer->useAsDefault) ? $customer->useAsDefault : 0);
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
            $data=json_decode(json_encode($data), true);
        }
        return isset($data['resourcesCustomersHidden']) && isset($data['id']);
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
     * Get all default assocs by $customerIds
     * If no $customerIds is provided, all default assoc will be loaded.
     * INFO: this function is used by useAsDefault filter in the language resources. Do not change the layout.
     * @param array $customerIds
     * @return array
     */
    public function loadByCustomerIdsDefault($customerIds=array()){
        $s=$this->db->select();
        if(!empty($customerIds)){
            $s->where('customerId IN(?)',$customerIds);
        }
        $s->where('useAsDefault=1');
        return $this->db->fetchAll($s)->toArray();
    }
    
    /***
     * Get all customers for $languageResourceId (languageResourceId)
     * @param int $languageResourceId
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
            array_push($retval[$assoc['languageResourceId']],$assoc);
        }
        return $retval;
    }
    
    /***
     * Find all default resources for user customers
     * @return array
     */
    public function findAsDefaultForUser(){
        $userModel=ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $userModel ZfExtended_Models_User */
        $customers=$userModel->getUserCustomersFromSession();
        
        if(empty($customers)){
            return [];
        }
        $s=$this->db->select()
        ->where('customerId IN(?)',$customers)
        ->where('useAsDefault=1')
        ->group('languageResourceId');
        
        $result=$this->db->fetchAll($s)->toArray();
        
        if(empty($result)){
            return [];
        }
        return array_column($result, 'languageResourceId');
    }
}

