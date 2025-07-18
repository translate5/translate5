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

namespace MittagQI\Translate5\LanguageResource\Operation;

use MittagQI\Translate5\LanguageResource\TaskAssociation;
use MittagQI\Translate5\Repository\LanguageResourceTaskAssocRepository;

class AssociateTaskOperation
{
    public function __construct(
        private readonly LanguageResourceTaskAssocRepository $taskAssocRepository
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            LanguageResourceTaskAssocRepository::create()
        );
    }

    public function associate(
        int $languageResourceId,
        string $taskGuid,
        bool $segmentsUpdatable = false,
        bool $autoCreateOnImport = false
    ): TaskAssociation {
        $taskAssoc = \ZfExtended_Factory::get(TaskAssociation::class);
        $taskAssoc->setLanguageResourceId($languageResourceId);
        $taskAssoc->setTaskGuid($taskGuid);
        $taskAssoc->setSegmentsUpdateable($segmentsUpdatable);
        $taskAssoc->setAutoCreatedOnImport((int) $autoCreateOnImport);

        try {
            $this->taskAssocRepository->save($taskAssoc);
        } catch (\ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey) {
            $taskAssoc = $this->taskAssocRepository->findByTaskGuidAndLanguageResource($taskGuid, $languageResourceId);
            $taskAssoc->setSegmentsUpdateable($segmentsUpdatable);
            $taskAssoc->setAutoCreatedOnImport((int) $autoCreateOnImport);

            $this->taskAssocRepository->save($taskAssoc);
        }

        return $taskAssoc;
    }
}
