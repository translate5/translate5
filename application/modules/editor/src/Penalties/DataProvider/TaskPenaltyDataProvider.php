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

namespace MittagQI\Translate5\Penalties\DataProvider;

use MittagQI\Translate5\LanguageResource\TaskAssociation;
use MittagQI\Translate5\Repository\LanguageRepository;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use MittagQI\Translate5\Repository\TaskRepository;
use ZfExtended_Factory;
use ZfExtended_Languages;

class TaskPenaltyDataProvider
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly LanguageResourceRepository $languageResourceRepository,
        private readonly LanguageRepository $languageRepository,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            TaskRepository::create(),
            LanguageResourceRepository::create(),
            LanguageRepository::create(),
        );
    }

    /**
     * @return int[]
     */
    public function getPenalties(
        string $taskGuid,
        int $resourceId,
        ?int $matchSourceLangId = null,
        ?int $matchTargetLangId = null
    ): array {
        $taskAssociation = ZfExtended_Factory::get(TaskAssociation::class);
        $taskAssociation->loadByTaskGuidAndTm($taskGuid, $resourceId);

        // Get task and it's source and target sublangs
        $task = $this->taskRepository->getByGuid($taskGuid);

        $subLang = [];
        $subLang['source']['task'] = ZfExtended_Languages::sublangCodeByRfc5646(
            $task->getSourceLanguage()->getRfc5646()
        );
        $subLang['target']['task'] = ZfExtended_Languages::sublangCodeByRfc5646(
            $task->getTargetLanguage()->getRfc5646()
        );

        $languageResource = $this->languageResourceRepository->get($resourceId);

        // Default value that will be kept active unless sublanguages mismatch detected
        $penaltySublang = 0;

        // Prepare languages (of match or resource) to be compared with languages of task
        $langIdToCompare = [
            'source' => $matchSourceLangId ?? $languageResource->getSourceLang(),
            'target' => $matchTargetLangId ?? $languageResource->getTargetLang(),
        ];

        // For source and target
        foreach (['source', 'target'] as $type) {
            $languageId = $langIdToCompare[$type];

            // If it's an array - it means resource is TermCollection but
            // neither $matchSourceLangId nor $matchTargetLangId are given
            // so we just pick the first among languages of a $type
            if ($languageId && is_array($languageId)) {
                $languageId = $languageId[0];
            }

            // Get sublang
            $subLang[$type]['langres'] = ZfExtended_Languages::sublangCodeByRfc5646(
                $this->languageRepository->get($languageId)->getRfc5646()
            );

            // If assoc langres sublang is not empty but does not match task sublang
            // apply the defined sublang penalty and exit the loop
            if ($subLang[$type]['langres'] && $subLang[$type]['langres'] !== $subLang[$type]['task']) {
                $penaltySublang = (int) $taskAssociation->getPenaltySublang();

                break;
            }
        }

        // Return penalties that should be really deducted from the matchrate
        return [
            'penaltyGeneral' => (int) $taskAssociation->getPenaltyGeneral(),
            'penaltySublang' => $penaltySublang,
        ];
    }
}
