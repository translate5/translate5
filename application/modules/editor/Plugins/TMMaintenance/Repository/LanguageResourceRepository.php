<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

declare(strict_types=1);

namespace MittagQI\Translate5\Plugins\TMMaintenance\Repository;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Services_Manager as ServicesManager;

class LanguageResourceRepository
{
    private \Zend_Db_Table_Abstract $db;

    public function __construct()
    {
        $this->db = \ZfExtended_Factory::get(LanguageResource::class)->db;
    }

    public function getT5MemoryTypeFilteredByCustomers(int ...$customerIds): array
    {
        if (empty($customerIds)) {
            return [];
        }

        $s = $this->getT5MemoryTypeSelect();
        $s->where('ca.customerId IN(?)', $customerIds);
        $result = $this->db->fetchAll($s)->toArray();

        return $this->mapLanguageCodes($result);
    }

    public function getT5MemoryType(): array
    {
        $s = $this->getT5MemoryTypeSelect();
        $result = $this->db->fetchAll($s)->toArray();

        return $this->mapLanguageCodes($result);
    }

    private function getT5MemoryTypeSelect(): \Zend_Db_Table_Select
    {
        return $this->db->select()
            ->from(
                [
                    'tm' => 'LEK_languageresources',
                ],
                ['tm.*']
            )
            ->setIntegrityCheck(false)
            ->joinLeft(
                [
                    'ca' => 'LEK_languageresources_customerassoc',
                ],
                'tm.id = ca.languageResourceId',
                ''
            )
            ->joinLeft(
                [
                    'c' => 'LEK_customer',
                ],
                'ca.customerId = c.id',
                'GROUP_CONCAT(`c`.`name`) as customers'
            )
            ->joinLeft(
                [
                    'l' => 'LEK_languageresources_languages',
                ],
                'tm.id = l.languageResourceId',
                [
                    'GROUP_CONCAT(`l`.`sourceLang`) as sourceLang',
                    'GROUP_CONCAT(`l`.`targetLang`) as targetLang',
                ]
            )
            ->where('tm.serviceType = ?', ServicesManager::SERVICE_OPENTM2)
            ->group('tm.id');
    }

    /**
     * Map all language codes to their rfc5646 representation in the given result as separate arrays(sourceLangCode
     * and targetLangCode). It will also convert the sourceLang and targetLang to arrays.
     */
    private function mapLanguageCodes(array $result = []): array
    {
        if (empty($result)) {
            return [];
        }

        $languages = \ZfExtended_Factory::get(\editor_Models_Languages::class);
        $languagesMapping = $languages->loadAllKeyValueCustom('id', 'rfc5646');

        // explode the language codes and map them to their rfc5646 representation
        $result = array_map(static function ($item) use ($languagesMapping) {
            $item['sourceLang'] = explode(',', $item['sourceLang']);
            $item['targetLang'] = explode(',', $item['targetLang']);

            foreach ($item['sourceLang'] as $langId) {
                $item['sourceLangCode'][] = $languagesMapping[$langId];
            }

            foreach ($item['targetLang'] as $langId) {
                $item['targetLangCode'][] = $languagesMapping[$langId];
            }

            return $item;
        }, $result);

        return $result;
    }
}
