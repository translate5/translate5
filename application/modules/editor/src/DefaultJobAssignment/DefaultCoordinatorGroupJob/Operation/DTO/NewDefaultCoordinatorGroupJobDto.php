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

namespace MittagQI\Translate5\DefaultJobAssignment\DefaultCoordinatorGroupJob\Operation\DTO;

use MittagQI\Translate5\DefaultJobAssignment\DTO\NewDefaultJobDto;
use MittagQI\Translate5\DefaultJobAssignment\DTO\TrackChangesRightsDto;
use MittagQI\Translate5\DefaultJobAssignment\DTO\WorkflowDto;
use MittagQI\Translate5\DefaultJobAssignment\Exception\InvalidLanguageIdProvidedException;

class NewDefaultCoordinatorGroupJobDto
{
    public function __construct(
        public readonly int $customerId,
        public readonly string $userGuid,
        public readonly int $sourceLanguageId,
        public readonly int $targetLanguageId,
        public readonly WorkflowDto $workflow,
        public readonly ?float $deadline,
        public readonly TrackChangesRightsDto $trackChangesRights,
    ) {
        if (0 === $sourceLanguageId) {
            throw new InvalidLanguageIdProvidedException('sourceLang');
        }

        if (0 === $targetLanguageId || $sourceLanguageId === $targetLanguageId) {
            throw new InvalidLanguageIdProvidedException('targetLang');
        }
    }

    public static function fromDefaultJobDto(NewDefaultJobDto $userJobDto): self
    {
        return new self(
            $userJobDto->customerId,
            $userJobDto->userGuid,
            $userJobDto->sourceLanguageId,
            $userJobDto->targetLanguageId,
            $userJobDto->workflow,
            $userJobDto->deadline,
            $userJobDto->trackChangesRights,
        );
    }
}
