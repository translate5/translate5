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

namespace MittagQI\Translate5\ContentProtection\T5memory;

use editor_Models_LanguageResources_LanguageResource;
use editor_Models_LanguageResources_Languages;
use editor_Models_Languages as Languages;
use MittagQI\Translate5\ContentProtection\Model\ContentProtectionRepository;
use MittagQI\Translate5\ContentProtection\Model\ContentRecognition;
use MittagQI\Translate5\ContentProtection\Model\InputMapping;
use MittagQI\Translate5\ContentProtection\Model\LanguageRulesHashService;
use MittagQI\Translate5\ContentProtection\Model\OutputMapping;
use MittagQI\Translate5\Repository\LanguageRepository;
use Zend_Db_Table_Select;
use ZfExtended_Factory;
use ZfExtended_Worker_Abstract;

/**
 * This worker recalculates hashes per language based on active rules (content recognition)
 * Once important fields of ContentRecognition are changed or InputMapping added/deleted hash should be recalculated
 */
class RecalculateRulesHashWorker extends ZfExtended_Worker_Abstract
{
    public const DIRECTION_INPUT = 0;

    public const DIRECTION_OUTPUT = 1;

    private ?int $recognitionId = null;

    private ?int $languageId = null;

    private int $direction = self::DIRECTION_INPUT;

    private ContentProtectionRepository $protectionRepository;

    private LanguageRepository $languageRepository;

    private LanguageRulesHashService $languageRulesHashService;

    private array $processedPairs = [];

    /**
     * @var array{sourceLang: int, targetLang: int}[]
     */
    private array $lrPairs;

    public function __construct()
    {
        parent::__construct();
        $this->protectionRepository = new ContentProtectionRepository();
        $this->languageRepository = new LanguageRepository();
        $this->languageRulesHashService = new LanguageRulesHashService(
            $this->protectionRepository,
            $this->languageRepository
        );
        $this->lrPairs = $this->getLangPairInT5MemoryLRs();
    }

    protected function validateParameters($parameters = [])
    {
        if (array_key_exists('direction', $parameters)) {
            $this->direction = (int) $parameters['direction'];
        }

        if (array_key_exists('recognitionId', $parameters)) {
            $this->recognitionId = (int) $parameters['recognitionId'];
        }

        if (array_key_exists('languageId', $parameters)) {
            $this->languageId = (int) $parameters['languageId'];
        }

        return true;
    }

    protected function work()
    {
        if (null !== $this->recognitionId) {
            $this->recalculateForRecognition($this->recognitionId);

            return true;
        }

        if (null !== $this->languageId) {
            $this->recalculateForLangs($this->direction, $this->languageId);

            return true;
        }

        $dbInputMapping = ZfExtended_Factory::get(InputMapping::class)->db;

        /** @var object{sourceLang: string, targetLang: string} $pair */
        foreach ($dbInputMapping->fetchAll($this->getSelectionBase()) as $pair) {
            $this->updateHashesFor((int) $pair->sourceLang, (int) $pair->targetLang);
        }

        $this->recalculateKeepAsIsPairs();

        return true;
    }

    private function recalculateKeepAsIsPairs(?int $recognitionId = null, ?int $sourceLang = null): void
    {
        $langs = $this->getLanguagesWithKeepAsIsRules($recognitionId, $sourceLang);

        $langModel = ZfExtended_Factory::get(Languages::class);

        foreach ($this->lrPairs as $pair) {
            if (in_array($pair['sourceLang'], $langs)) {
                $this->updateHashesFor($pair['sourceLang'], $pair['targetLang']);

                continue;
            }

            $langModel->load($pair['sourceLang']);

            if ($langModel->getMajorRfc5646() === $langModel->getRfc5646()) {
                continue;
            }

            $major = ZfExtended_Factory::get(Languages::class);
            $major->loadByRfc5646($langModel->getMajorRfc5646());

            if (in_array($major->getId(), $langs)) {
                $this->updateHashesFor($pair['sourceLang'], $pair['targetLang']);
            }
        }
    }

    /**
     * @return array{sourceLang: int, targetLang: int}[]
     *
     * @throws \ReflectionException
     * @throws \Zend_Db_Table_Exception
     */
    private function getLangPairInT5MemoryLRs(): array
    {
        $lrLanguageDb = ZfExtended_Factory::get(editor_Models_LanguageResources_Languages::class)->db;
        $languageResourceDb = ZfExtended_Factory::get(editor_Models_LanguageResources_LanguageResource::class)->db;

        $select = $lrLanguageDb->select()
            ->setIntegrityCheck(false)
            ->from([
                'lrLanguage' => $lrLanguageDb->info($lrLanguageDb::NAME),
            ], ['sourceLang', 'targetLang'])
            ->join(
                [
                    'languageResource' => $languageResourceDb->info($languageResourceDb::NAME),
                ],
                'languageResource.id = lrLanguage.languageResourceId',
                []
            )
            ->where('languageResource.serviceType = ?', 'editor_Services_OpenTM2')
        ;

        $pairs = [];

        /** @var \stdClass{sourceLang: string, targetLang: string} $pair */
        foreach ($lrLanguageDb->fetchAll($select) as $pair) {
            $pairs["$pair->sourceLang:$pair->targetLang"] = [
                'sourceLang' => (int) $pair->sourceLang,
                'targetLang' => (int) $pair->targetLang,
            ];
        }

        return array_values($pairs);
    }

    /**
     * @return int[]
     */
    private function getLanguagesWithKeepAsIsRules(?int $recognitionId, ?int $sourceLang): array
    {
        $dbInputMapping = ZfExtended_Factory::get(InputMapping::class)->db;
        $dbContentRecognition = ZfExtended_Factory::get(ContentRecognition::class)->db;
        $contentRecognitionTable = $dbContentRecognition->info($dbContentRecognition::NAME);

        $select = $dbInputMapping->select()
            ->setIntegrityCheck(false)
            ->from([
                'inputMapping' => $dbInputMapping->info($dbInputMapping::NAME),
            ], ['distinct(languageId)'])
            ->join(
                [
                    'recognition' => $contentRecognitionTable,
                ],
                'recognition.id = inputMapping.contentRecognitionId',
                []
            )
            ->where('recognition.enabled = true')
            ->where('recognition.keepAsIs = true')
        ;

        if (null !== $sourceLang) {
            $select->where('inputMapping.languageId = ?', $sourceLang);
        }

        if (null !== $recognitionId) {
            $select->orWhere('recognition.id = ?', $recognitionId);
        }

        $langs = $dbInputMapping->fetchAll($select)->toArray();

        return array_column($langs, 'languageId');
    }

    private function recalculateForRecognition(int $recognitionId): void
    {
        $select = $this->getSelectionBase()
            ->where('inputContentRecognitionId = ?', $recognitionId)
            ->orWhere('outputContentRecognitionId = ?', $recognitionId)
        ;

        $this->processSelection($select);

        $this->recalculateKeepAsIsPairs($recognitionId);
    }

    private function recalculateSubLanguages(int $sourceLang): void
    {
        $inputLang = ZfExtended_Factory::get(Languages::class);
        $inputLang->load($sourceLang);

        // if the language is NOT a major language then all good
        if ($inputLang->getMajorRfc5646() !== $inputLang->getRfc5646()) {
            return;
        }

        $sourceLang = ZfExtended_Factory::get(Languages::class);
        // Recalculate for all pairs where the source language is a sub-language
        foreach ($this->lrPairs as $lrPair) {
            $sourceLang->load($lrPair['sourceLang']);

            if ($sourceLang->getMajorRfc5646() === $inputLang->getRfc5646()) {
                $this->updateHashesFor((int) $sourceLang->getId(), $lrPair['targetLang']);
            }
        }
    }

    private function getSelectionBase(bool $onlyActiveRules = false): Zend_Db_Table_Select
    {
        $dbInputMapping = ZfExtended_Factory::get(InputMapping::class)->db;
        $dbOutputMapping = ZfExtended_Factory::get(OutputMapping::class)->db;
        $dbContentRecognition = ZfExtended_Factory::get(ContentRecognition::class)->db;
        $contentRecognitionTable = $dbContentRecognition->info($dbContentRecognition::NAME);

        return $dbInputMapping->select()
            ->setIntegrityCheck(false)
            ->from(
                [
                    'InputMapping' => $dbInputMapping->info($dbInputMapping::NAME),
                ],
                ['InputMapping.languageId as sourceLang']
            )
            ->join(
                [
                    'inputRecognition' => $contentRecognitionTable,
                ],
                'inputRecognition.id = InputMapping.contentRecognitionId'
                . ($onlyActiveRules ? ' AND inputRecognition.enabled = true' : ''),
                []
            )
            ->join(
                [
                    'OutputMapping' => $dbOutputMapping->info($dbOutputMapping::NAME),
                ],
                'InputMapping.contentRecognitionId = OutputMapping.inputContentRecognitionId',
                ['OutputMapping.languageId as targetLang']
            )
            ->join(
                [
                    'outputRecognition' => $contentRecognitionTable,
                ],
                'outputRecognition.id = OutputMapping.outputContentRecognitionId'
                . ($onlyActiveRules ? ' AND inputRecognition.enabled = true' : ''),
                []
            )
            ->where('InputMapping.languageId != OutputMapping.languageId')
            ->group(['sourceLang', 'targetLang'])
        ;
    }

    private function recalculateForLangs(int $direction, int $languageId): void
    {
        $select = null;

        if (self::DIRECTION_INPUT === $direction) {
            $select = $this->getSelectionBase(true)->where('InputMapping.languageId = ?', $languageId);

            // Recalculate hashes here as if mapping is deleted the hash may not be recalculated by $select processing
            foreach ($this->languageRulesHashService->findAllBySourceLang($languageId) as $hash) {
                $this->updateHashesFor((int) $hash->getSourceLanguageId(), (int) $hash->getTargetLanguageId());
            }

            $this->recalculateKeepAsIsPairs(sourceLang: $languageId);
        }

        if (self::DIRECTION_OUTPUT === $direction) {
            $select = $this->getSelectionBase(true)->where('OutputMapping.languageId = ?', $languageId);

            // Recalculate hashes here as if mapping is deleted the hash may not be recalculated by $select processing
            foreach ($this->languageRulesHashService->findAllByTargetLang($languageId) as $hash) {
                $this->updateHashesFor((int) $hash->getSourceLanguageId(), (int) $hash->getTargetLanguageId());
            }
        }

        if (null === $select) {
            return;
        }

        $this->processSelection($select);

        $this->recalculateKeepAsIsPairs();
    }

    private function updateHashesFor(int $sourceLang, int $targetLang): void
    {
        $processedKey = $sourceLang . ':' . $targetLang;

        if (isset($this->processedPairs[$processedKey])) {
            return;
        }

        $this->processedPairs[$processedKey] = true;

        $this->languageRulesHashService->updateBy($sourceLang, $targetLang);
    }

    private function processSelection(Zend_Db_Table_Select $select): void
    {
        $dbInputMapping = ZfExtended_Factory::get(InputMapping::class)->db;

        /** @var object{sourceLang: string, targetLang: string} $pair */
        foreach ($dbInputMapping->fetchAll($select) as $pair) {
            $this->updateHashesFor((int) $pair->sourceLang, (int) $pair->targetLang);
            $this->recalculateSubLanguages((int) $pair->sourceLang);
        }
    }
}
