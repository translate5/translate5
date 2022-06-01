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
 * Customer Entity Objekt
 * 
 * @method integer getId() getId()
 * @method void setId() setId(int $id)
 * 
 * @method string getName() getName()
 * @method void setName() setName(string $name)
 * 
 * @method string getNumber() getNumber()
 * @method void setNumber() setNumber(string $number)
 * 
 * @method integer getSearchCharacterLimit() getSearchCharacterLimit()
 * @method void setSearchCharacterLimit() setSearchCharacterLimit(int $searchCharacterLimit)
 * 
 * @method string getDomain() getDomain()
 * @method void setDomain() setDomain(string $domain)
 * 
 * @method string getOpenIdServer() getOpenIdServer()
 * @method void setOpenIdServer() setOpenIdServer(string $openIdServer)
 * 
 * @method string getOpenIdIssuer() getOpenIdIssuer()
 * @method void setOpenIdIssuer() setOpenIdIssuer(string $openIdIssuer)
 * 
 * @method string getOpenIdAuth2Url() getOpenIdAuth2Url()
 * @method void setOpenIdAuth2Url() setOpenIdAuth2Url(string $openIdAuth2Url)
 * 
 * @method string getOpenIdServerRoles() getOpenIdServerRoles()
 * @method void setOpenIdServerRoles() setOpenIdServerRoles(string $openIdServerRoles)
 * 
 * @method string getOpenIdDefaultServerRoles() getOpenIdDefaultServerRoles()
 * @method void setOpenIdDefaultServerRoles() setOpenIdDefaultServerRoles(string $openIdDefaultServerRoles)
 * 
 * @method string getOpenIdClientId() getOpenIdClientId()
 * @method void setOpenIdClientId() setOpenIdClientId(string $openIdClientId)
 * 
 * @method string getOpenIdClientSecret() getOpenIdClientSecret()
 * @method void setOpenIdClientSecret() setOpenIdClientSecret(string $openIdClientSecret)
 * 
 * @method string getOpenIdRedirectLabel() getOpenIdRedirectLabel()
 * @method void setOpenIdRedirectLabel() setOpenIdRedirectLabel(string $openIdRedirectLabel)
 * 
 * @method integer getOpenIdRedirectCheckbox() getOpenIdRedirectCheckbox()
 * @method void setOpenIdRedirectCheckbox() setOpenIdRedirectCheckbox(integer $openIdRedirectCheckbox)
 * 
 * 
*/
class editor_Models_Customer_Customer extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_Customer';
    protected $validatorInstanceClass   = 'editor_Models_Validator_Customer';
    
    CONST DEFAULTCUSTOMER_NUMBER = 'default for legacy data';
    /**
     * @var editor_Models_Customer_Meta|null
     */
    protected ?editor_Models_Customer_Meta $meta;

    public function delete() {
        $customerId = $this->getId();
        parent::delete();
        $logger = ZfExtended_Factory::get('editor_Models_LanguageResources_UsageLogger');
        /* @var $logger editor_Models_LanguageResources_UsageLogger */
        //remove the log data for the deleted customer
        $logger->deleteByCustomer($customerId);
    }
    
    /**
     *  Get the customer specific config for current customer.
     *  If there is no customer overwritte for the config, the instance level value will be used.
     * @return Zend_Config
     */
    public function getConfig() {
        $customerConfig = ZfExtended_Factory::get('editor_Models_Customer_CustomerConfig');
        /* @var $customerConfig editor_Models_Customer_CustomerConfig */
        return $customerConfig->getCustomerConfig($this->getId());
    }
    
    /**
     * Loads customers by a given list of ids
     * @param array $ids
     * @return array
     */
    public function loadByIds(array $ids){
        $s=$this->db->select()
        ->where('id IN (?)', array_unique($ids));
        return $this->loadFilterdCustom($s);
    }
    
    /***
     * Load customer by number
     * @param string $number
     */
    public function loadByNumber($number){
        try {
            $s = $this->db->select()->where('`number` = ?', $number);
            $row = $this->db->fetchRow($s);
        } catch (Exception $e) {
            $this->notFound('NotFound after other Error', $e);
        }
        if (!$row) {
            $this->notFound(__CLASS__ . '#number', $number);
        }
        //load implies loading one Row, so use only the first row
        $this->row = $row;
    }
    
    /***
     * Search customers by given search string.
     * The search will provide any match on name field.
     *
     * @param string $searchString
     * @return array|array
     */
    public function search($searchString,$fields=array()) {
        $s = $this->db->select();
        if(!empty($fields)){
            $s->from($this->tableName,$fields);
        }
        $s->where('lower(name) LIKE lower(?)','%'.$searchString.'%');
        return $this->db->fetchAll($s)->toArray();
    }

    /***
     * Get min characters from given customers
     * @param array $customers
     * @return array
     */
    public function getMinSearchCharacters(array $customers) {
        if(empty($customers)){
            return array();
        }
        $s = $this->db->select()
        ->from($this->tableName,array('MIN(searchCharacterLimit) as searchCharacterLimit'))
        ->where('id IN(?)', $customers);
        return $this->db->fetchRow($s)->toArray();
    }
    
    /***
     * Return minimum search characters for user customers.
     * If no user model is provided, the session user customers will be used
     * 
     * @param ZfExtended_Models_User $user
     */
    public function getMinCharactersByUser(ZfExtended_Models_User $user=null){
        $customers=array();
        //no user, use the session user
        if(!isset($user)){
            $user=ZfExtended_Factory::get('ZfExtended_Models_User');
            /* @var $user ZfExtended_Models_User */
            $customers=$user->getUserCustomersFromSession();
        }else{
            $customers=$user->getCustomers();
            if(!empty($customers)){
                $customers=trim($customers,",");
                $customers=explode(',', $customers);
            }
        }
        
        $ret=$this->getMinSearchCharacters($customers);
        if(!empty($ret) && isset($ret['searchCharacterLimit'])){
            return $ret['searchCharacterLimit'];
        }
        return 0;
    }
    
    /**
     * Load the default customer and return it
     * @return editor_Models_Customer_Customer
     */
    public function loadByDefaultCustomer(): editor_Models_Customer_Customer {
        $this->loadRow('number=?',self::DEFAULTCUSTOMER_NUMBER);
        return $this;
    }
    
    /***
     * Load customer entity by given openid domain
     * @param string $domain
     */
    public function loadByDomain($domain) {
        $s = $this->db->select();
        $s->where('domain=?',$domain);
        $row=$this->db->fetchRow($s);
        if(empty($row)){
            return;
        }
        $this->row =$row;
    }

    /**
     * convenient method to get the customer meta data
     * @param bool $reinit if true reinits the internal meta object completely (after adding a field for example)
     * @return editor_Models_customer_Meta
     */
    public function meta(bool $reinit = false) {
        /** @var editor_Models_Customer_Meta $meta */
        $meta = $this->meta ?? $this->meta = ZfExtended_Factory::get('editor_Models_Customer_Meta');
        $customerId = $this->getId();
        if($meta->getCustomerId() != $customerId || $reinit){
            try {
                $meta->loadByCustomerId($customerId);
            } catch(ZfExtended_Models_Entity_NotFoundException){
                $meta->init(['customerId' => $customerId]);
            }
        }
        return $meta;
    }
    
    /***
     * Is the customer the default customer?
     * @return boolean
     */
    public function isDefaultCustomer(){
        return ($this->getNumber() == self::DEFAULTCUSTOMER_NUMBER);
    }
    
    public function __toString() {
        return $this->getName().' ('.$this->getNumber().'; id: '.$this->getId().')';
    }
}
