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
 * @method integer getLanguageResourceId getLanguageResourceId()
 * @method void setLanguageResourceId() setLanguageResourceId(int $languageResourceId)
 * @method integer getSourceLang() getSourceLang()
 * @method void setSourceLang() setSourceLang(int $sourceLang)
 * @method integer getTargetLang() getTargetLang()
 * @method void setTargetLang() setTargetLang(int $targetLang)
 * @method string getQueryString() getQueryString()
 * @method void setQueryString() setQueryString(string $queryString)
 * @method string getRequestSource() getRequestSource()
 * @method void setRequestSource() setRequestSource(string $requestSource)
 * @method integer getTranslatedCharacterCount() getTranslatedCharacterCount()
 * @method void setTranslatedCharacterCount() setTranslatedCharacterCount(int $translatedCharacterCount)
 * @method string getTimestamp() getTimestamp()
 * @method void setTimestamp() setTimestamp(string $timestamp)
 * @method string getCustomers() getCustomers()
 * @method void setCustomers() setCustomers(string $customerId)
 * 
 * 
 */
class editor_Models_LanguageResources_MtUsageLogger extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_LanguageResources_MtUsageLogger';
    protected $validatorInstanceClass = 'editor_Models_Validator_LanguageResources_MtUsageLogger';
    
    
    public function loadByCustomer($customerId){
        $s=$this->db->select()
        ->from(array("ul" => "LEK_languageresources_mt_usage_log"), array("ul.*"))
        ->setIntegrityCheck(false)
        ->join(array("lr" => "LEK_languageresources"),"lr.id = ul.languageResouceId","lr.name AS languageResourceName","lr.serviceName AS serviceName")
        ->where('customers LIKE %,?,%',$this->db->getAdapter()->quote($customerId));
        return $this->db->fetchAll($s)->toArray();
    }
    
    public function save() {
        parent::save();
        //update the totals
        $this->updateSumTable();
    }
    
    /***
     * Update the total sum collection after the current entity is saved.
     */
    protected function updateSumTable() {
        $sum = ZfExtended_Factory::get('editor_Models_LanguageResources_MtUsageSumLogger');
        /* @var $sum editor_Models_LanguageResources_MtUsageSumLogger */
        $sum->updateSumTable($this);
    }
    
    /***
     * Delete log entries for the given customerId. 
     * For log entires with multiple customers, only the given customer will be removed.
     * @param int $customerId
     */
    public function deleteByCustomer(int $customerId) {
        $sql = "UPDATE `LEK_languageresources_mt_usage_log` SET `customers` = replace(customers, ',?,', ',')";
        $this->db->getAdapter()->query($sql,$customerId);
        $this->db->delete([
            'customers = ? OR customers="," ' => $customerId // Check for empty rows. The above query can leave single , as value
        ]);
        
    }
}

