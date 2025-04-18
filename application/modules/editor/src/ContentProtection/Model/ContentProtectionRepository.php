<?php
/*
START LICENSE AND COPYRIGHT
 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file is part of a paid plug-in for translate5.

 The translate5 core software and its freely downloadable plug-ins are licensed under an AGPLv3 open-source license
 (https://www.gnu.org/licenses/agpl-3.0.en.html).
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 Paid translate5 plugins can deviate from standard AGPLv3 licensing and therefore constitute an
 exception. As such, translate5 plug-ins can be licensed under either AGPLv3 or GPLv3 (see below for details).

 Briefly summarized, a GPLv3 license dictates the same conditions as its AGPLv3 variant, except that it
 does not require the program (plug-in, in this case) to direct users toward its download location if it is
 only being used via the web in a browser.
 This enables developers to write custom plug-ins for translate5 and keep them private, granted they
 meet the GPLv3 licensing conditions stated above.
 As the source code of this paid plug-in is under open source GPLv3 license, everyone who did obtain
 the source code could pass it on for free or paid to other companies or even put it on the web for
 free download for everyone.

 As this would undermine completely the financial base of translate5s development and the translate5
 community, we at MittagQI would not longer support a company or supply it with updates for translate5,
 that would pass on the source code to third parties.

 Of course as long as the code stays within the company who obtained it, you are free to do
 everything you want with the source code (within the GPLv3 boundaries), like extending it or installing
 it multiple times.

 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html

 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5. This plug-in exception allows using GPLv3 for translate5 plug-ins,
 although translate5 core is licensed under AGPLv3.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/gpl.html
             http://www.translate5.net/plugin-exception.txt
END LICENSE AND COPYRIGHT
*/
declare(strict_types=1);

namespace MittagQI\Translate5\ContentProtection\Model;

use editor_Models_Db_LanguageResources_LanguageResource;
use editor_Models_Db_LanguageResources_Languages;
use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Models_Languages as Languages;
use editor_Services_Manager;
use MittagQI\Translate5\ContentProtection\DTO\RulesHashDto;
use MittagQI\Translate5\ContentProtection\NumberProtection\Protector\KeepContentProtector;
use MittagQI\Translate5\ContentProtection\NumberProtection\Protector\ReplaceContentProtector;
use MittagQI\Translate5\Repository\LanguageRepository;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Table;
use Zend_Db_Table_Select;
use ZfExtended_Factory;

class ContentProtectionRepository
{
    public function __construct(
        private readonly Zend_Db_Adapter_Abstract $db,
        private readonly LanguageRepository $languageRepository,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            Zend_Db_Table::getDefaultAdapter(),
            LanguageRepository::create(),
        );
    }

    /**
     * @var array<string, ContentProtectionDto[]>
     */
    private array $cachedQueryResults = [];

    public function hasActiveTextRules(?Languages $sourceLang): bool
    {
        if (null === $sourceLang) {
            return false;
        }

        $dbInputMapping = ZfExtended_Factory::get(InputMapping::class)->db;
        $dbContentRecognition = ZfExtended_Factory::get(ContentRecognition::class)->db;
        $contentRecognitionTable = $dbContentRecognition->info($dbContentRecognition::NAME);

        $sourceIds = [(int) $sourceLang->getId()];

        $major = $this->languageRepository->findByRfc5646($sourceLang->getMajorRfc5646());

        if ($sourceLang->getMajorRfc5646() !== $sourceLang->getRfc5646() && null !== $major) {
            $sourceIds[] = (int) $major->getId();
        }

        $select = $dbInputMapping->select()
            ->setIntegrityCheck(false)
            ->from([
                'inputMapping' => $dbInputMapping->info($dbInputMapping::NAME),
            ], [])
            ->join(
                [
                    'recognition' => $contentRecognitionTable,
                ],
                'recognition.id = inputMapping.contentRecognitionId',
                ['count(recognition.id) as count']
            )
            ->where('inputMapping.languageId IN (?)', $sourceIds)
            ->where('recognition.enabled = true')
            ->where('recognition.type IN (?)', [KeepContentProtector::getType(), ReplaceContentProtector::getType()])
            ->order('priority desc')
        ;

        $row = $dbInputMapping->fetchRow($select)->toArray();

        return $row['count'] > 0;
    }

    public function hasActiveRules(?Languages $sourceLang, ?Languages $targetLang): bool
    {
        if (null === $sourceLang || null === $targetLang) {
            return false;
        }

        return ! empty($this->getAllForSource($sourceLang, $targetLang));
    }

    /**
     * @return iterable<ContentProtectionDto>
     */
    public function getAllForSource(Languages $sourceLang, Languages $targetLang, bool $useCache = true): iterable
    {
        $cacheKey = sprintf('%s-%s-source', $sourceLang->getRfc5646(), $targetLang->getRfc5646());

        if ($useCache && isset($this->cachedQueryResults[$cacheKey])) {
            return $this->cachedQueryResults[$cacheKey];
        }

        $dbInputMapping = ZfExtended_Factory::get(InputMapping::class)->db;
        $dbOutputMapping = ZfExtended_Factory::get(OutputMapping::class)->db;
        $dbContentRecognition = ZfExtended_Factory::get(ContentRecognition::class)->db;
        $contentRecognitionTable = $dbContentRecognition->info($dbContentRecognition::NAME);

        $sourceIds = [(int) $sourceLang->getId()];
        $targetIds = [(int) $targetLang->getId()];

        $major = $this->languageRepository->findByRfc5646($sourceLang->getMajorRfc5646());

        if ($sourceLang->getMajorRfc5646() !== $sourceLang->getRfc5646() && null !== $major) {
            $sourceIds[] = (int) $major->getId();
        }

        $major = $this->languageRepository->findByRfc5646($targetLang->getMajorRfc5646());

        if ($targetLang->getMajorRfc5646() !== $targetLang->getRfc5646() && null !== $major) {
            $targetIds[] = (int) $major->getId();
        }

        $select = $dbInputMapping->select()
            ->setIntegrityCheck(false)
            ->from([
                'inputMapping' => $dbInputMapping->info($dbInputMapping::NAME),
            ], ['priority'])
            ->join(
                [
                    'recognition' => $contentRecognitionTable,
                ],
                'recognition.id = inputMapping.contentRecognitionId',
                ['recognition.*']
            )
            ->join(
                [
                    'outputMapping' => $dbOutputMapping->info($dbOutputMapping::NAME),
                ],
                'outputMapping.languageId IN (' . implode(',', $targetIds) . ')
                AND outputMapping.inputContentRecognitionId = inputMapping.contentRecognitionId',
                []
            )
            ->join(
                [
                    'outputRecognition' => $contentRecognitionTable,
                ],
                'outputRecognition.id = outputMapping.outputContentRecognitionId
                AND outputRecognition.enabled = true',
                ['outputRecognition.format as outputFormat']
            )
            ->where('inputMapping.languageId IN (?)', $sourceIds)
            ->where('recognition.enabled = true')
            ->where('recognition.keepAsIs = false')
            ->order('priority desc')
        ;

        $rows = array_merge(
            $dbInputMapping->fetchAll($this->getKeepAsIsSelect($sourceIds))->toArray(),
            $dbInputMapping->fetchAll($select)->toArray(),
        );

        $collection = [];
        foreach ($rows as $formatData) {
            $collection[] = ContentProtectionDto::fromRow($formatData);
        }

        if ($useCache && ! isset($this->cachedQueryResults[$cacheKey])) {
            $this->cachedQueryResults[$cacheKey] = $collection;
        }

        return $collection;
    }

    /**
     * @return iterable<ContentProtectionDto>
     */
    public function getAllForTarget(Languages $sourceLang, Languages $targetLang, bool $useCache = true): iterable
    {
        $cacheKey = sprintf('%s-%s-target', $sourceLang->getRfc5646(), $targetLang->getRfc5646());

        if ($useCache && isset($this->cachedQueryResults[$cacheKey])) {
            return $this->cachedQueryResults[$cacheKey];
        }

        $dbInputMapping = ZfExtended_Factory::get(InputMapping::class)->db;
        $dbOutputMapping = ZfExtended_Factory::get(OutputMapping::class)->db;
        $dbContentRecognition = ZfExtended_Factory::get(ContentRecognition::class)->db;
        $contentRecognitionTable = $dbContentRecognition->info($dbContentRecognition::NAME);

        $sourceIds = [$sourceLang->getId()];
        $targetIds = [$targetLang->getId()];

        $major = $this->languageRepository->findByRfc5646($sourceLang->getMajorRfc5646());

        if ($sourceLang->getMajorRfc5646() !== $sourceLang->getRfc5646() && null !== $major) {
            $sourceIds[] = $major->getId();
        }

        $major = $this->languageRepository->findByRfc5646($targetLang->getMajorRfc5646());

        if ($targetLang->getMajorRfc5646() !== $targetLang->getRfc5646() && null !== $major) {
            $targetIds[] = $major->getId();
        }

        $select = $dbOutputMapping->select()
            ->setIntegrityCheck(false)
            ->from([
                'outputMapping' => $dbOutputMapping->info($dbOutputMapping::NAME),
            ], [])
            ->join(
                [
                    'recognition' => $contentRecognitionTable,
                ],
                'recognition.id = outputMapping.outputContentRecognitionId',
                ['recognition.*']
            )
            ->join(
                [
                    'inputMapping' => $dbInputMapping->info($dbInputMapping::NAME),
                ],
                'inputMapping.languageId IN (' . implode(',', $sourceIds) . ')
                AND outputMapping.inputContentRecognitionId = inputMapping.contentRecognitionId',
                ['priority']
            )
            ->join(
                [
                    'inputRecognition' => $contentRecognitionTable,
                ],
                'inputRecognition.id = outputMapping.inputContentRecognitionId
                AND inputRecognition.enabled = true',
                ['inputRecognition.format as outputFormat']
            )
            ->where('outputMapping.languageId IN (?)', $targetIds)
            ->where('recognition.enabled = true')
            ->where('recognition.keepAsIs = false')
            ->orWhere('recognition.keepAsIs = true')
            ->order('priority desc')
        ;

        $rows = array_merge(
            $dbOutputMapping->fetchAll($this->getKeepAsIsSelect($sourceIds))->toArray(),
            $dbOutputMapping->fetchAll($select)->toArray(),
        );

        $collection = [];
        foreach ($rows as $formatData) {
            $collection[] = ContentProtectionDto::fromRow($formatData);
        }

        if ($useCache && ! isset($this->cachedQueryResults[$cacheKey])) {
            $this->cachedQueryResults[$cacheKey] = $collection;
        }

        return $collection;
    }

    public function getContentRecognitionForOutputMappingForm(): array
    {
        $dbMapping = ZfExtended_Factory::get(InputMapping::class)->db;
        $dbContentRecognition = ZfExtended_Factory::get(ContentRecognition::class)->db;
        $contentRecognitionTable = $dbContentRecognition->info($dbContentRecognition::NAME);

        $select = $dbMapping->select()
            ->setIntegrityCheck(false)
            ->from([
                'mapping' => $dbMapping->info($dbMapping::NAME),
            ], [])
            ->join(
                [
                    'recognition' => $contentRecognitionTable,
                ],
                'recognition.id = mapping.contentRecognitionId',
                ['recognition.id', 'recognition.type', 'recognition.name']
            )
            ->where('recognition.enabled = true')
            ->order('name asc')
        ;

        return $dbMapping->fetchAll($select)->toArray();
    }

    public function getContentRecognitionForInputMappingForm(): array
    {
        $dbContentRecognition = ZfExtended_Factory::get(ContentRecognition::class)->db;
        $contentRecognitionTable = $dbContentRecognition->info($dbContentRecognition::NAME);

        $select = $dbContentRecognition->select()
            ->from(
                [
                    'recognition' => $contentRecognitionTable,
                ],
                ['recognition.id', 'recognition.type', 'recognition.name']
            )
            ->where('recognition.enabled = true')
            ->order('name desc')
        ;

        return $dbContentRecognition->fetchAll($select)->toArray();
    }

    public function getRulesHashBy(Languages $sourceLang, Languages $targetLang): ?string
    {
        $inputLines = [];

        foreach ($this->getAllForSource($sourceLang, $targetLang, false) as $dto) {
            $inputLines[] = sprintf(
                '%s:%s:%s:%s:%s:%s',
                $dto->regex,
                $dto->matchId,
                (int) $dto->keepAsIs,
                $dto->format,
                $dto->outputFormat,
                $dto->priority
            );
        }

        return md5(implode('|', $inputLines));
    }

    /**
     * @return array<int, array{int, string}>
     */
    public function getLanguageRulesHashMap(): array
    {
        $db = ZfExtended_Factory::get(LanguageRulesHash::class)->db;
        $select = $db->select()->from([
            'hashes' => $db->info($db::NAME),
        ], ['*']);

        $map = [];

        foreach ($db->fetchAll($select)->toArray() as $row) {
            $map[(int) $row['sourceLanguageId']][(int) $row['targetLanguageId']] = $row['hash'];
        }

        return $map;
    }

    /**
     * @return array<int, RulesHashDto>
     */
    public function getLanguageResourceRulesHashMap(): array
    {
        $select = $this->getBaseRuleHashSelect();

        $hashes = [];

        /** @var array{id: int, specificData: string, sourceLang: string, targetLang: string} $row */
        foreach ($this->db->fetchAll($select) as $row) {
            $id = $row['id'];
            if (! isset($hashes[$id])) {
                $hashes[$id] = [];
            }

            $specificData = $row['specificData'] ? json_decode($row['specificData'], true) : [];

            $hashes[$id] = new RulesHashDto(
                (int) $id,
                [
                    'source' => (int) $row['sourceLang'],
                    'target' => (int) $row['targetLang'],
                ],
                $specificData[LanguageResource::PROTECTION_HASH] ?? null,
                (bool) ($specificData[LanguageResource::PROTECTION_CONVERSION_SCHEDULED] ?? null),
                (bool) ($specificData[LanguageResource::PROTECTION_CONVERSION_STARTED] ?? null),
            );
        }

        return $hashes;
    }

    private function getKeepAsIsSelect(array $sourceIds): Zend_Db_Table_Select
    {
        $dbInputMapping = ZfExtended_Factory::get(InputMapping::class)->db;
        $dbContentRecognition = ZfExtended_Factory::get(ContentRecognition::class)->db;
        $contentRecognitionTable = $dbContentRecognition->info($dbContentRecognition::NAME);

        return $dbInputMapping->select()
            ->setIntegrityCheck(false)
            ->from([
                'inputMapping' => $dbInputMapping->info($dbInputMapping::NAME),
            ], ['priority'])
            ->join(
                [
                    'recognition' => $contentRecognitionTable,
                ],
                'recognition.id = inputMapping.contentRecognitionId',
                ['recognition.*']
            )
            ->where('inputMapping.languageId IN (?)', $sourceIds)
            ->where('recognition.enabled = true')
            ->where('recognition.keepAsIs = true')
            ->order('priority desc');
    }

    private function getBaseRuleHashSelect(): \Zend_Db_Select
    {
        return $this->db
            ->select()
            ->from([
                'LanguageResource' => editor_Models_Db_LanguageResources_LanguageResource::TABLE_NAME,
            ], ['id', 'specificData'])
            ->join(
                [
                    'LRLanguages' => editor_Models_Db_LanguageResources_Languages::TABLE_NAME,
                ],
                'LRLanguages.languageResourceId = LanguageResource.id',
                ['LRLanguages.sourceLang', 'LRLanguages.targetLang']
            )
            ->where('LanguageResource.serviceType = ?', editor_Services_Manager::SERVICE_OPENTM2);
    }
}
