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
 * @method integer getCustomerId() getCustomerId()
 * @method void setCustomerId() setCustomerId(int $customerId)
 *
 * @method integer getUseAsDefault() getUseAsDefault()
 * @method void setUseAsDefault() setUseAsDefault(int $useAsDefault)
 * 
 * @method integer getWriteAsDefault() getWriteAsDefault()
 * @method void setWriteAsDefault() setWriteAsDefault(int $writeAsDefault)
 *
 * @method integer getPivotAsDefault() getPivotAsDefault()
 * @method void setPivotAsDefault() setPivotAsDefault(int $pivotAsDefault)
 * 
 */
class editor_Models_LanguageResources_CustomerAssoc extends ZfExtended_Models_Entity_Abstract {
    
    protected $dbInstanceClass = 'editor_Models_Db_LanguageResources_CustomerAssoc';
    protected $validatorInstanceClass = 'editor_Models_Validator_LanguageResources_CustomerAssoc';
    
    /***
     * Save customer assoc from the request parameters for the given language resource.
     * A language resource that is saved must have at least one customer assigned
     * (if none is given, we use the defaultcustomer).
     * @param int $id
     * @param array $data : request parametars
     */
    public function saveAssocRequest(int $id, array $data){
        
        $customers = $data['customerIds'] ?? [];
        $useAsDefault = $data['customerUseAsDefaultIds'] ?? [];
        $writeAsDefault = $data['customerWriteAsDefaultIds'] ?? [];
        $pivotAsDefault = $data['customerPivotAsDefaultIds'] ?? [];

        // Check if (at least one) customer is set and use the 'defaultcustomer' if not
        if (empty($customers)) {
            $customer = ZfExtended_Factory::get('editor_Models_Customer_Customer');
            /* @var $customer editor_Models_Customer_Customer */
            $customer->loadByDefaultCustomer();
            $customers[] = $customer->getId();
        }
        
        // ensure that only useAsDefault customers are used, which are added also as customers
        $useAsDefault = array_intersect($useAsDefault, $customers);

        // ensure that only writeAsDefault customers are used, which are added also as useAsDefault(read as default)
        $writeAsDefault = array_intersect($writeAsDefault, $useAsDefault);

        // ensure that only pivotAsDefault customers are used, which are added also as customers
        $pivotAsDefault = array_intersect($pivotAsDefault, $customers);
        
        $this->addAssocs($id, $customers, $useAsDefault,$writeAsDefault,$pivotAsDefault);
    }
    
    /**
     * Update customer assoc from the request parameters.
     *
     * @param int $id
     * @param stdClass $data
     */
    public function updateAssocRequest(int $id, array $data){
        // remove old assocs for the current languageResourceId
        $this->db->delete(['languageResourceId IN (?)' => $id]);
        
        // save the new data
        $this->saveAssocRequest($id, $data);
    }
    
    /***
     * Add customer to language resources association into the database.
     * 
     * @param int $languageResourceId
     * @param array $customers
     * @param array $useAsDefault : list of all customers which useAsDefault is 1
     * @param array $writeAsDefault : list of all customers which writeAsDefault is 1
     * @param array $pivotAsDefault : list of all customers which pivotAsDefault is 1
     */
    public function addAssocs(int $languageResourceId, array $customers, array $useAsDefault = [], array $writeAsDefault = [], array $pivotAsDefault = []){
        foreach ($customers as $id){
            $model = ZfExtended_Factory::get('editor_Models_LanguageResources_CustomerAssoc');
            /* @var $model editor_Models_LanguageResources_CustomerAssoc */
            $model->setCustomerId($id);
            $model->setLanguageResourceId($languageResourceId);
            $model->setUseAsDefault(in_array($id, $useAsDefault));
            $model->setWriteAsDefault(in_array($id, $writeAsDefault));
            $model->setPivotAsDefault(in_array($id, $pivotAsDefault));
            $model->save();
        }
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
    public function loadByCustomerIds(array $customerIds = []){
        $s=$this->getCustomerIdsSelect($customerIds);
        return $this->db->fetchAll($s)->toArray();
    }
    
    /***
     * @param array $customerIds
     * @return Zend_Db_Table_Select
     */
    protected function getCustomerIdsSelect(array $customerIds = []){
        $s=$this->db->select();
        if(!empty($customerIds)){
            $s->where('customerId IN(?)',$customerIds);
        }
        return $s;
    }
    
    /***
     * Get all useAsDefault customer assocs for givent customer ids.
     * If no $customerIds is provided, all results where useAsDefault is set to 1 will be returned.
     * INFO: this function is used by useAsDefault filter in the language resources. Do not change the layout.
     * @param array $customerIds
     * @param string $column If given, array_column(<returnValue>, $column) will be returned instead of <returnValue>
     * @return array
     */
    public function loadByCustomerIdsUseAsDefault(array $customerIds = [], string $column = ''){
        $s=$this->getCustomerIdsSelect($customerIds);
        $s->where('useAsDefault=1');
        $return = $this->db->fetchAll($s)->toArray();
        return $column ? array_column($return, $column) : $return;
    }
    
    /***
     * Get all writeAsDefault customer assocs for given customer ids
     * If no $customerIds is provided, all results where writeAsDefault is set to 1 will be returned.
     * INFO: this function is used by writeAsDefault filter in the language resources. Do not change the layout.
     * @param array $customerIds
     * @return array
     */
    public function loadByCustomerIdsWriteAsDefault(array $customerIds = []): array{
        $s=$this->getCustomerIdsSelect($customerIds);
        $s->where('writeAsDefault=1');
        return $this->db->fetchAll($s)->toArray();
    }
    
    /***
     * Get all pivotAsDefault customer assocs for given customer ids
     * If no $customerIds is provided, all results where pivotAsDefault is set to 1 will be returned.
     * INFO: this function is used by pivotAsDefault filter in the language resources. Do not change the layout.
     * @param array $customerIds
     * @return array
     */
    public function loadByCustomerIdsPivotAsDefault(array $customerIds = []): array{
        $s=$this->getCustomerIdsSelect($customerIds);
        $s->where('pivotAsDefault=1');
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
    
    /***
     * Load all customer ids for the given language resources
     * @param array $languageResourceIds
     * @return array
     */
    public function loadLanguageResourcesCustomers(array $languageResourceIds) {
        $s=$this->db->select()
        ->from('LEK_languageresources_customerassoc',['distinct(customerId) as customers'])
        ->where('languageResourceId IN(?)',$languageResourceIds);
        $result=$this->db->fetchAll($s)->toArray();
        if(empty($result)){
            return [];
        }
        return array_column($result, 'customers');
    }

    /**
     * Get customers, having access for all given collections
     *
     * @param array $collectionIds
     * @throws Zend_Db_Statement_Exception
     */
    public function getSharedCustomers(array $collectionIds) {

        // Get customer ids, grouped by collectionId
        $customerIdAByCollectionId = $this->db->getAdapter()->query('
            SELECT `languageResourceId`, `customerId`  
            FROM `LEK_languageresources_customerassoc` 
            WHERE FIND_IN_SET(`languageResourceId`, ?) 
        ', join(',', $collectionIds))->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_COLUMN);

        // Get customers, assigned to all given term collections, e.g. shared customers
        return call_user_func_array('array_intersect', $customerIdAByCollectionId);
    }
}

