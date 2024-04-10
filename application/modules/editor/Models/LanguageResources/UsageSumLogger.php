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

/***
* @method void setId(int $id)
* @method string getId()
* @method void setLangageResourceId(int $langageResourceId)
* @method string getLangageResourceId()
* @method void setLangageResourceName(string $langageResourceName)
* @method string getLangageResourceName()
* @method void setLangageResourceType(string $langageResourceType)
* @method string getLangageResourceType()
* @method void setSourceLang(int $sourceLang)
* @method string getSourceLang()
* @method void setTargetLang(int $targetLang)
* @method string getTargetLang()
* @method void setCustomerId(int $customerId)
* @method string getCustomerId()
* @method void setYearAndMonth(string $yearAndMonth)
* @method string getYearAndMonth()
* @method void setTotalCharacters(int $totalCharacters)
* @method string getTotalCharacters()
*/

class editor_Models_LanguageResources_UsageSumLogger extends ZfExtended_Models_Entity_Abstract
{
    protected $dbInstanceClass = "editor_Models_Db_LanguageResources_UsageSumLogger";

    protected $validatorInstanceClass = "editor_Models_Validator_LanguageResources_UsageSumLogger";

    /***
     * Load resource resources and year month summary log data
     * @param int $customerId
     * @return array
     */
    public function loadMonthlySummaryByResource(int $customerId = null): array
    {
        $fields = [
            'log.langageResourceName',
            'lr.serviceName as langageResourceServiceName',
            'log.sourceLang',
            'log.targetLang',
            'log.yearAndMonth',
            'SUM(log.totalCharacters) AS totalCharacters',
        ];
        if (! empty($customerId)) {
            array_unshift($fields, 'log.customerId');
        }
        $s = $this->db->select()
            ->setIntegrityCheck(false)
            ->from([
                'log' => 'LEK_languageresources_usage_log_sum',
            ], $fields)
            ->join([
                'lr' => 'LEK_languageresources',
            ], 'lr.id = log.langageResourceId', []);
        if (! empty($customerId)) {
            $s->where('log.customerId = ?', $customerId);
        }
        $s->group(['log.customerId', 'log.langageResourceId', 'log.yearAndMonth']);

        return $this->db->fetchAll($s)->toArray();
    }

    /***
     * Update sum record for given resource usage log entry. For each customer in the $log entry, separate
     * update/insert is executed (see updateInsertTotalCharacters function)
     *
     * @param editor_Models_LanguageResources_UsageLogger $log
     */
    public function updateSumTable(editor_Models_LanguageResources_UsageLogger $log)
    {
        //for each customer, we update separate row
        $customers = explode(',', $log->getCustomers());
        $customers = array_filter($customers);
        if (empty($customers)) {
            return;
        }

        $languageResource = ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
        /* @var $languageResource editor_Models_LanguageResources_LanguageResource */
        $languageResource->load($log->getLanguageResourceId());

        //TODO: make sence ? check me with the task definition
        $characterPerCustomer = round($log->getTranslatedCharacterCount() / count($customers));
        $this->setTotalCharacters($characterPerCustomer);

        $this->setLangageResourceId($log->getLanguageResourceId()); //part of the unique key
        $this->setLangageResourceName($languageResource->getName());
        $this->setLangageResourceType($languageResource->getResourceType());

        $this->setSourceLang($log->getSourceLang()); //part of the unique key
        $this->setTargetLang($log->getTargetLang()); //part of the unique key

        //set the year-month for the current record
        $this->setYearAndMonth(date('Y-m')); //part of the unique key

        foreach ($customers as $logCustomer) {
            $this->setCustomerId($logCustomer); //part of the unique key
            $this->updateInsertTotalCharacters();
        }
    }

    /***
     * Update or insert the total characters from the current record. If the record with unique key(`langageResourceId`,`sourceLang`,`targetLang`,`customerId`,`yearAndMonth`)
     * already exist, the total characters will be added to the current row characters.
     */
    public function updateInsertTotalCharacters()
    {
        $sql = "INSERT INTO LEK_languageresources_usage_log_sum (langageResourceId, langageResourceName, langageResourceType, sourceLang, targetLang, customerId, yearAndMonth, totalCharacters) 
                VALUES (?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE totalCharacters=totalCharacters+?;";
        $this->db->getAdapter()->query($sql, [
            $this->getLangageResourceId(),
            $this->getLangageResourceName(),
            $this->getLangageResourceType(),
            $this->getSourceLang(),
            $this->getTargetLang(),
            $this->getCustomerId(),
            $this->getYearAndMonth(),
            $this->getTotalCharacters(),
            $this->getTotalCharacters(), //this is for duplicate update
        ]);
    }
}
