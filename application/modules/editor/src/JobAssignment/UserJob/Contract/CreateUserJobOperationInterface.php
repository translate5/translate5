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

namespace MittagQI\Translate5\JobAssignment\UserJob\Contract;

use editor_Models_TaskUserAssoc as UserJob;
use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\JobAssignment\Exception\ConfirmedCompetitiveJobAlreadyExistsException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\AttemptToAssignCoordinatorGroupUserJobBeforeCoordinatorGroupJobCreatedException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\CoordinatorHasNotConfirmedCoordinatorGroupJobYetException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\OnlyCoordinatorCanBeAssignedToCoordinatorGroupJobException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\OnlyOneUniqueCoordinatorGroupJobCanBeAssignedPerTaskException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\TrackChangesRightsAreNotSubsetOfCoordinatorGroupJobException;
use MittagQI\Translate5\JobAssignment\UserJob\Operation\DTO\NewUserJobDto;
use MittagQI\Translate5\Task\Exception\InexistentTaskException;
use MittagQI\Translate5\User\Exception\InexistentUserException;

interface CreateUserJobOperationInterface
{
    /**
     * @throws ConfirmedCompetitiveJobAlreadyExistsException
     * @throws CoordinatorHasNotConfirmedCoordinatorGroupJobYetException
     * @throws InexistentUserException
     * @throws \ZfExtended_NotAuthenticatedException
     * @throws \ZfExtended_NotFoundException
     * @throws PermissionExceptionInterface
     * @throws AttemptToAssignCoordinatorGroupUserJobBeforeCoordinatorGroupJobCreatedException
     * @throws InexistentTaskException
     * @throws OnlyCoordinatorCanBeAssignedToCoordinatorGroupJobException
     * @throws OnlyOneUniqueCoordinatorGroupJobCanBeAssignedPerTaskException
     * @throws TrackChangesRightsAreNotSubsetOfCoordinatorGroupJobException
     */
    public function assignJob(NewUserJobDto $dto): UserJob;
}
