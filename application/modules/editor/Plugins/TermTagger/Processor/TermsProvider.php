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

declare(strict_types=1);

namespace MittagQI\Translate5\Plugins\TermTagger\Processor;

use editor_Models_Languages;
use editor_Models_TermCollection_TermCollection;
use editor_Models_Terminology_Models_TermModel;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Table_Abstract;
use ZfExtended_Factory;

class TermsProvider
{
    public function __construct(
        private readonly Zend_Db_Adapter_Abstract $db,
        private readonly editor_Models_Terminology_Models_TermModel $termModel,
        private readonly editor_Models_Languages $languages,
        private readonly editor_Models_TermCollection_TermCollection $termCollection,
    ) {
    }

    public static function create()
    {
        return new self(
            Zend_Db_Table_Abstract::getDefaultAdapter(),
            ZfExtended_Factory::get(editor_Models_Terminology_Models_TermModel::class),
            ZfExtended_Factory::get(editor_Models_Languages::class),
            ZfExtended_Factory::get(editor_Models_TermCollection_TermCollection::class)
        );
    }

    public function retrieveTermsBasedOnTaggedString(
        string $sourceText,
        int $sourceLanguage,
        int $targetLanguage,
        string $taskGuid
    ): array {
        $termIds = $this->termModel->getTermMidsFromSegment($sourceText);

        if (empty($termIds)) {
            return [];
        }

        $sourceFuzzyLanguages = $this->languages->getFuzzyLanguages($sourceLanguage, 'id', true);
        $targetFuzzyLanguages = $this->languages->getFuzzyLanguages($targetLanguage, 'id', true);

        $collectionIds = $this->termCollection->getCollectionsForTask($taskGuid);

        $tbxId = array_unique($termIds);

        $languages = array_merge($sourceFuzzyLanguages, $targetFuzzyLanguages);

        $existsSql = "
            SELECT DISTINCT `termTbxId`, `termEntryTbxId`, `term` 
            FROM `terms_term` 
            WHERE `termTbxId` IN ('" . implode("','", $tbxId) . "')
              AND `collectionId` IN (" . implode(',', $collectionIds) . ")
              AND `processStatus` = 'finalized'          
        ";

        $exists = $this->db->query($existsSql)->fetchAll(\PDO::FETCH_UNIQUE);

        // Get all terms (from source and target), grouped by their termEntryTbxId
        return $this->db->query('
            SELECT `termEntryTbxId`, `termEntryTbxId`, `term`, `termTbxId`, `languageId`, `status`,
                CASE WHEN `languageId` IN (' . implode(',', $sourceFuzzyLanguages) . ") THEN 1 ELSE 0 END AS `isSource`
            FROM `terms_term`
            WHERE `termEntryTbxId` IN ('" . implode("','", array_column($exists, 'termEntryTbxId')) . "')
              AND `collectionId`   IN (" . implode(',', $collectionIds) . ')
              AND `languageId`     IN (' . implode(',', $languages) . ")
              AND `processStatus` = 'finalized'
            ORDER BY FIND_IN_SET(`status`, 'preferredTerm,standardizedTerm') DESC,
                 NOT FIND_IN_SET(`status`, 'deprecatedTerm,supersededTerm') DESC,
              `status` = 'admittedTerm' ASC
        ")->fetchAll(\PDO::FETCH_GROUP);
    }
}
