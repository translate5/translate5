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

namespace MittagQI\Translate5\Test\Fixtures\DefaultJobAssignment;

use editor_Models_UserAssocDefault as DefaultUserJob;
use MittagQI\Translate5\DefaultJobAssignment\DefaultCoordinatorGroupJob\Model\DefaultCoordinatorGroupJob;

class DefaultCoordinatorGroupJobFixtures
{
    public function createFor(
        int $groupId,
        int $customerId,
        string $userGuid,
        int $sourceLang,
        int $targetLang,
        string $workflow,
        string $workflowStepName,
        bool $trackChangesShow,
        bool $trackChangesShowAll,
        bool $trackChangesAcceptReject,
    ): DefaultCoordinatorGroupJob {
        $defaultUserJob = new DefaultUserJob();
        $defaultUserJob->setCustomerId($customerId);
        $defaultUserJob->setUserGuid($userGuid);
        $defaultUserJob->setSourceLang($sourceLang);
        $defaultUserJob->setTargetLang($targetLang);
        $defaultUserJob->setWorkflow($workflow);
        $defaultUserJob->setWorkflowStepName($workflowStepName);
        $defaultUserJob->setTrackchangesShow((int) $trackChangesShow);
        $defaultUserJob->setTrackchangesShowAll((int) $trackChangesShowAll);
        $defaultUserJob->setTrackchangesAcceptReject((int) $trackChangesAcceptReject);

        $defaultUserJob->save();

        $defaultLspJob = new DefaultCoordinatorGroupJob();
        $defaultLspJob->setGroupId($groupId);
        $defaultLspJob->setCustomerId($customerId);
        $defaultLspJob->setSourceLang($sourceLang);
        $defaultLspJob->setTargetLang($targetLang);
        $defaultLspJob->setWorkflow($workflow);
        $defaultLspJob->setWorkflowStepName($workflowStepName);
        $defaultLspJob->setDataJobId((int) $defaultUserJob->getId());

        $defaultLspJob->save();

        return $defaultLspJob;
    }
}
