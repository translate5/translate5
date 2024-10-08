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

namespace MittagQI\Translate5\CrossSynchronization;

use editor_Models_LanguageResources_CustomerAssoc as CustomerAssoc;
use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Models_LanguageResources_Languages as LanguageResourceLanguages;
use editor_Models_Languages as Language;
use MittagQI\Translate5\CrossSynchronization\Dto\PotentialConnectionOption;
use MittagQI\Translate5\Repository\LanguageRepository;
use Zend_Db_Table_Row;
use ZfExtended_Factory;

class ConnectionOptionsRepository
{
    public function __construct(
        private readonly LanguageRepository $languageRepository,
    ) {
    }

    public static function create(?LanguageRepository $languageRepository = null): self
    {
        return new self(
            $languageRepository ?: LanguageRepository::create(),
        );
    }

    /**
     * @return iterable<PotentialConnectionOption>
     */
    public function getPotentialConnectionOptions(LanguageResource $source): iterable
    {
        $db = $source->db;

        $lrLangTable = ZfExtended_Factory::get(LanguageResourceLanguages::class)->db->info($db::NAME);
        $lrCustomerTable = ZfExtended_Factory::get(CustomerAssoc::class)->db->info($db::NAME);

        [$sourceLangIds, $sourceLangs, $sourceAddedMajorToSourceMap] = $this->composeLangStructures(
            ...array_map('intval', (array) $source->getSourceLang())
        );
        [$targetLangIds, $targetLangs, $targetAddedMajorToTargetMap] = $this->composeLangStructures(
            ...array_map('intval', (array) $source->getTargetLang())
        );

        $lrsWithSameCustomersSelect = $db->select()
            ->setIntegrityCheck(false)
            ->distinct()
            ->from(
                [
                    'LanguageResources' => $db->info($db::NAME),
                ],
            )
            ->join(
                [
                    'LanguageResourceLanguages' => $lrLangTable,
                ],
                'LanguageResourceLanguages.languageResourceId = LanguageResources.id',
                [
                    'sourceLangId' => 'LanguageResourceLanguages.sourceLang',
                    'targetLangId' => 'LanguageResourceLanguages.targetLang',
                ]
            )
            ->join(
                [
                    'CustomerAssoc' => $lrCustomerTable,
                ],
                'CustomerAssoc.languageResourceId = LanguageResources.id',
                []
            )
            ->where('LanguageResourceLanguages.sourceLang IN (?)', $sourceLangIds)
            ->where('LanguageResourceLanguages.targetLang IN (?)', $targetLangIds)
            ->where('CustomerAssoc.customerId IN (?)', $source->getCustomers())
            ->where('LanguageResources.id != ?', $source->getId())
            ->order('LanguageResources.serviceName');

        $stmt = $db->getAdapter()->query($lrsWithSameCustomersSelect);

        /**
         * If for target LR_T->sourceLang = EN and source LR_S->sourceLang = [EN-GB, EN-US], then LR1 and LR2 are related
         * LR_T will be returned as an option for connection to LR_S
         * But we have to provide LR_S twice:
         *  - once with sourceLang = EN-GB of LR_S
         *  - once with sourceLang = EN-US of LR_S
         * And user will have to choose in UI which language should be used as source of data for synchronization
         *
         * Same for targetLang
         *
         * @param int $langId
         * @param array<int, Language> $langList
         * @param Language $langMap
         * @return iterable<Language>
         */
        $getResultLangList = function (int $langId, array $langList, array $langMap): iterable {
            $list = [];

            if (array_key_exists($langId, $langList)) {
                $list = [
                    $langList[$langId],
                ];
            }

            if (! array_key_exists($langId, $langMap)) {
                return $list;
            }

            foreach ($langMap[$langId] as $lang) {
                $list[] = $lang;
            }

            return $list;
        };

        $lr = ZfExtended_Factory::get(LanguageResource::class);

        while ($row = $stmt->fetch(\Zend_Db::FETCH_ASSOC)) {
            $resultSourceLangs = $getResultLangList(
                (int) $row['sourceLangId'],
                $sourceLangs,
                $sourceAddedMajorToSourceMap
            );

            $resultTargetLangs = $getResultLangList(
                (int) $row['targetLangId'],
                $targetLangs,
                $targetAddedMajorToTargetMap
            );

            unset($row['sourceLangId'], $row['targetLangId']);

            $lr->init(
                new Zend_Db_Table_Row(
                    [
                        'table' => $db,
                        'data' => $row,
                        'stored' => true,
                        'readOnly' => false,
                    ]
                )
            );

            foreach ($resultSourceLangs as $sourceLang) {
                foreach ($resultTargetLangs as $targetLang) {
                    yield new PotentialConnectionOption(clone $lr, $sourceLang, $targetLang);
                }
            }
        }
    }

    /**
     * @return array{int[], array<int, Language>, array<int, array<int, Language>>}
     */
    private function composeLangStructures(int ...$langIds): array
    {
        $langs = [];
        $addedMajorToLangMap = [];

        $resultLangIds = [];

        foreach ($langIds as $langId) {
            if (isset($langs[$langId])) {
                continue;
            }

            $sourceLang = $this->languageRepository->get($langId);
            $langs[$langId] = $sourceLang;
            $resultLangIds[$langId] = true;

            $major = $this->languageRepository->findByRfc5646($sourceLang->getMajorRfc5646());

            if ($sourceLang->getMajorRfc5646() !== $sourceLang->getRfc5646() && null !== $major) {
                $resultLangIds[(int) $major->getId()] = true;

                if (! isset($addedMajorToLangMap[(int) $major->getId()])) {
                    $addedMajorToLangMap[(int) $major->getId()] = [];
                }

                $addedMajorToLangMap[(int) $major->getId()][(int) $sourceLang->getId()] = $sourceLang;
            }
        }

        return [array_keys($resultLangIds), $langs, $addedMajorToLangMap];
    }
}
