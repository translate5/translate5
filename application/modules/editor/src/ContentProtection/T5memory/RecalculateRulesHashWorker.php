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

use editor_Models_Languages;
use MittagQI\Translate5\ContentProtection\Model\ContentProtectionRepository;
use MittagQI\Translate5\ContentProtection\Model\InputMapping;
use MittagQI\Translate5\ContentProtection\Model\LanguageRulesHash;
use MittagQI\Translate5\ContentProtection\Model\OutputMapping;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_Worker_Abstract;

/**
 * This worker recalculates hashes per language based on active rules (content recognition)
 * Once important fields of ContentRecognition are changed or InputMapping added/deleted hash should be recalculated
 */
class RecalculateRulesHashWorker extends ZfExtended_Worker_Abstract
{
    public const DIRECTION_BOTH = 0;
    public const DIRECTION_INPUT = 1;
    public const DIRECTION_OUTPUT = 2;

    private ?int $recognitionId = null;
    private int $languageId = 0;
    private int $direction = self::DIRECTION_BOTH;
    private ContentProtectionRepository $repository;

    public function __construct()
    {
        parent::__construct();
        $this->repository = new ContentProtectionRepository();
    }

    protected function validateParameters($parameters = [])
    {
        if (array_key_exists('direction', $parameters)) {
            $this->direction = (int) $parameters['direction'];
        }

        if (array_key_exists('recognitionId', $parameters)) {
            $this->recognitionId = (int) $parameters['recognitionId'];

            return true;
        }

        if (array_key_exists('languageId', $parameters)) {
            $this->languageId = (int) $parameters['languageId'];

            return true;
        }

        return false;
    }
    
    protected function work()
    {
        if (null !== $this->recognitionId) {
            $this->recalculateForRecognition($this->recognitionId);

            return true;
        }

        $this->recalculateForLangs($this->direction, $this->languageId);

        return true;
    }

    private function recalculateForRecognition(int $recognitionId): void
    {
        $dbMapping = ZfExtended_Factory::get(InputMapping::class)->db;
        $select = $dbMapping->select()
            ->from(['mapping' => $dbMapping->info($dbMapping::NAME)], ['distinct(languageId)'])
            ->where('contentRecognitionId = ?', $recognitionId)
        ;

        $this->recalculateForLangs(
            self::DIRECTION_INPUT,
            ...array_column($dbMapping->fetchAll($select)->toArray(), 'languageId')
        );

        $dbMapping = ZfExtended_Factory::get(OutputMapping::class)->db;
        $select = $dbMapping->select()
            ->from(['mapping' => $dbMapping->info($dbMapping::NAME)], ['distinct(languageId)'])
            ->where('outputContentRecognitionId = ?', $recognitionId)
        ;

        $this->recalculateForLangs(
            self::DIRECTION_OUTPUT,
            ...array_column($dbMapping->fetchAll($select)->toArray(), 'languageId')
        );
    }

    private function recalculateForLangs(int $direction, int|string ...$languageIds): void
    {
        $language = ZfExtended_Factory::get(editor_Models_Languages::class);
        $languageRulesHash = ZfExtended_Factory::get(LanguageRulesHash::class);

        foreach ($languageIds as $languageId) {
            $language->load($languageId);

            try {
                $languageRulesHash->loadByLanguageId((int) $languageId);
            } catch (ZfExtended_Models_Entity_NotFoundException) {
                // if not found we simply create new
                $languageRulesHash->init();
                $languageRulesHash->setLanguageId((int) $languageId);
            }

            if (in_array($direction, [self::DIRECTION_INPUT, self::DIRECTION_BOTH], true)) {
                $languageRulesHash->setInputHash($this->repository->getInputRulesHashBy($language));
            }
            if (in_array($direction, [self::DIRECTION_OUTPUT, self::DIRECTION_BOTH], true)) {
                $languageRulesHash->setOutputHash($this->repository->getOutputRulesHashBy($language));
            }

            $languageRulesHash->save();
        }
    }
}