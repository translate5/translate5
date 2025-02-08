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

use MittagQI\Translate5\LanguageResource\Provider\LanguageResourceProvider;
use MittagQI\Translate5\Repository\LanguageRepository;
use MittagQI\Translate5\Repository\TaskRepository;
use ZfExtended_Languages as Languages;

class TaskPenaltyDataProvider
{
    private array $sublangPenaltyMeta = [];

    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly LanguageRepository $languageRepository,
        private readonly LanguageResourceProvider $languageResourceProvider,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            TaskRepository::create(),
            LanguageRepository::create(),
            LanguageResourceProvider::create(),
        );
    }

    /**
     * Check whether there is a source or target sublanguages mismatch between task and languageresource,
     * and if so - return the penalties to be applied, or return zero penalties
     *
     * @param array|null $langPairOfTheMatch If given - expected to be ['source' => 123, 'target' => 234]
     * @throws \ReflectionException
     * @throws \ZfExtended_Exception
     * @throws \ZfExtended_Models_Entity_NotFoundException
     */
    public function getPenalties(string $taskGuid, int $langresId, ?array $langPairOfTheMatch = null): array
    {
        // If meta for the given $taskGuid is not cached so far - prepare it and cache
        if (! isset($this->sublangPenaltyMeta[$taskGuid])) {
            $task = $this->taskRepository->getByGuid($taskGuid);

            // Load meta data containing single sublanguage and penalties for each possible and existing task<=>langres assoc
            $meta = $this->languageResourceProvider->getAssocTasksWithResources($taskGuid);
            $meta = array_combine(array_column($meta, 'languageResourceId'), $meta);

            // Put into cache along with task sublanguages, if any
            $this->sublangPenaltyMeta[$taskGuid] = [
                'meta' => $meta,
                'subLang' => [
                    'source' => [
                        'task' => Languages::sublangCodeByRfc5646($task->getSourceLanguage()->getRfc5646()),
                    ],
                    'target' => [
                        'task' => Languages::sublangCodeByRfc5646($task->getTargetLanguage()->getRfc5646()),
                    ],
                ],
            ];
        }

        // Extract meta
        $meta = $this->sublangPenaltyMeta[$taskGuid]['meta'];
        $subLang = $this->sublangPenaltyMeta[$taskGuid]['subLang'];

        // If $langresId refers to an assignable resource
        if (! isset($meta[$langresId])) {
            return [
                'penaltyGeneral' => 0,
                'penaltySublang' => 0,
            ];
        }

        // For source and target
        foreach (['source', 'target'] as $type) {
            $languageId = $langPairOfTheMatch[$type] ?? $meta[$langresId][$type . 'Lang'];
            $lang = $this->languageRepository->get((int) $languageId);

            // Get sublanguage
            $subLang[$type]['langres'] = Languages::sublangCodeByRfc5646($lang->getRfc5646());

            // If both task and langres have sublanguages, but they don't match - return sublang penalty
            if (
                $subLang[$type]['task']
                && $subLang[$type]['langres']
                && $subLang[$type]['langres'] !== $subLang[$type]['task']
            ) {
                return [
                    'penaltyGeneral' => $meta[$langresId]['penaltyGeneral'],
                    'penaltySublang' => $meta[$langresId]['penaltySublang'],
                ];
            }
        }

        // If we reached this line, it means sublanguages mismatch was not detected, so 0-penalty should be applied
        return [
            'penaltyGeneral' => $meta[$langresId]['penaltyGeneral'],
            'penaltySublang' => 0,
        ];
    }
}
