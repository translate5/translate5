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

    public function __construct()
    {
        parent::__construct();
        $this->protectionRepository = new ContentProtectionRepository();
        $this->languageRepository = new LanguageRepository();
        $this->languageRulesHashService = new LanguageRulesHashService(
            $this->protectionRepository,
            $this->languageRepository
        );
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

        foreach ($dbInputMapping->fetchAll($this->getSelectionBase()) as $pair) {
            $this->updateHashesFor((int) $pair->sourceLang, (int) $pair->targetLang);
        }

        return true;
    }

    private function recalculateForRecognition(int $recognitionId): void
    {
        $dbInputMapping = ZfExtended_Factory::get(InputMapping::class)->db;

        $select = $this->getSelectionBase()->where('contentRecognitionId = ?', $recognitionId);

        foreach ($dbInputMapping->fetchAll($select) as $pair) {
            $this->updateHashesFor((int) $pair->sourceLang, (int) $pair->targetLang);
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
        $dbInputMapping = ZfExtended_Factory::get(InputMapping::class)->db;

        $select = null;

        if (self::DIRECTION_INPUT === $direction) {
            $select = $this->getSelectionBase(true)->where('InputMapping.languageId = ?', $languageId);

            foreach ($this->languageRulesHashService->findAllBySourceLang($languageId) as $hash) {
                $this->updateHashesFor((int) $hash->getSourceLanguageId(), (int) $hash->getTargetLanguageId());
            }
        }

        if (self::DIRECTION_OUTPUT === $direction) {
            $select = $this->getSelectionBase(true)->where('OutputMapping.languageId = ?', $languageId);

            foreach ($this->languageRulesHashService->findAllByTargetLang($languageId) as $hash) {
                $this->updateHashesFor((int) $hash->getSourceLanguageId(), (int) $hash->getTargetLanguageId());
            }
        }

        if (null === $select) {
            return;
        }

        foreach ($dbInputMapping->fetchAll($select) as $pair) {
            $this->updateHashesFor((int) $pair->sourceLang, (int) $pair->targetLang);
        }
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
}
