<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\JobAssignment;

use MittagQI\Translate5\CoordinatorGroup\Exception\CoordinatorDontBelongToLCoordinatorGroupException;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\ActionAssert\Feasibility\Exception\ThereIsUnDeletableBoundJobException;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Exception\CoordinatorGroupJobAlreadyExistsException;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Exception\CoordinatorOfParentGroupHasNotConfirmedCoordinatorGroupJobYetException;
use MittagQI\Translate5\JobAssignment\Exception\ConfirmedCompetitiveJobAlreadyExistsException;
use MittagQI\Translate5\JobAssignment\Exception\InvalidTypeProvidedException;
use MittagQI\Translate5\JobAssignment\UserJob\ActionAssert\Feasibility\Exception\AttemptToRemoveJobInUseException;
use MittagQI\Translate5\JobAssignment\UserJob\ActionAssert\Feasibility\Exception\AttemptToRemoveJobWhichTaskIsLockedByUserException;
use MittagQI\Translate5\JobAssignment\UserJob\ActionAssert\Feasibility\Exception\UserHasAlreadyOpenedTheTaskForEditingException;
use MittagQI\Translate5\JobAssignment\UserJob\ActionAssert\Permission\Exception\ActionNotAllowedException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\AssignedUserCanBeChangedOnlyForCoordinatorGroupJobException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\AttemptToAssignCoordinatorGroupUserJobBeforeCoordinatorGroupJobCreatedException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\AttemptToAssignSubCoordinatorGroupJobBeforeParentJobCreatedException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\CoordinatorHasNotConfirmedCoordinatorGroupJobYetException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\InvalidDeadlineDateStringProvidedException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\InvalidSegmentRangeFormatException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\InvalidSegmentRangeSemanticException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\InvalidStateProvidedException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\JobAlreadyExistsException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\NotCoordinatorGroupCustomerTaskException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\OnlyCoordinatorCanBeAssignedToCoordinatorGroupJobException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\TrackChangesRightsAreNotSubsetOfCoordinatorGroupJobException;
use MittagQI\Translate5\Segment\QualityService;
use MittagQI\Translate5\Task\Exception\InexistentTaskException;
use MittagQI\Translate5\Task\Exception\TaskHasCriticalQualityErrorsException;
use MittagQI\Translate5\User\Exception\InexistentUserException;
use MittagQI\ZfExtended\Localization;
use Throwable;
use Zend_Registry;
use ZfExtended_ErrorCodeException;
use ZfExtended_Logger;
use ZfExtended_Models_Entity_Conflict as EntityConflictException;
use ZfExtended_UnprocessableEntity as UnprocessableEntity;

class JobExceptionTransformer
{
    public function __construct(
        private readonly ZfExtended_Logger $logger,
    ) {
        UnprocessableEntity::addCodes([
            'E1012' => 'Multi Purpose Code logging in the context of jobs (task user association)',
            'E1280' => 'The format of the segment range assigned to the user is not valid.',
        ]);

        EntityConflictException::addCodes([
            'E1061' => 'The job can not be removed, since the user is using the task.',
            'E1062' => 'The job can not be removed, since the task is locked by the user.',
            'E1161' => 'The job can not be modified, since the user has already opened the task for editing.'
                . ' You are to late.',
            'E1542' => QualityService::ERROR_MASSAGE_PLEASE_SOLVE_ERRORS,
        ]);
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            Zend_Registry::get('logger')->cloneMe('job-assignment.job-exception-transformer'),
        );
    }

    /**
     * @throws Throwable
     */
    public function transformException(Throwable $e): ZfExtended_ErrorCodeException|Throwable
    {
        $invalidValueProvidedMessage = Localization::trans('Invalid value provided');

        return match ($e::class) {
            InvalidTypeProvidedException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'type' => [
                        $invalidValueProvidedMessage,
                    ],
                ],
            ),
            InexistentUserException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'userGuid' => [
                        $invalidValueProvidedMessage,
                    ],
                ],
            ),
            InexistentTaskException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'taskGuid' => [
                        $invalidValueProvidedMessage,
                    ],
                ],
            ),
            InvalidStateProvidedException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'state' => [
                        $invalidValueProvidedMessage,
                    ],
                ],
            ),
            InvalidDeadlineDateStringProvidedException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'deadlineDate' => [
                        $invalidValueProvidedMessage,
                    ],
                ],
            ),
            UserHasAlreadyOpenedTheTaskForEditingException::class => EntityConflictException::createResponse(
                'E1161',
                [
                    'id' => Localization::trans(
                        'The job can not be modified, since the user ' .
                        'has already opened the task for editing.'
                    ),
                ]
            ),
            InvalidSegmentRangeFormatException::class => UnprocessableEntity::createResponse(
                'E1280',
                [
                    'id' => Localization::trans(
                        'The format of the segment range assigned to the user is not valid. Example: 1-3,5,8-9'
                    ),
                ]
            ),
            InvalidSegmentRangeSemanticException::class => UnprocessableEntity::createResponse(
                'E1280',
                [
                    'id' => Localization::trans(
                        'The content of the segment range assigned to the user is not ' .
                        'valid. Make sure that the values are not reverse and do not ' .
                        'overlap (neither in itself nor with other users of the same role).'
                    ),
                ]
            ),
            TaskHasCriticalQualityErrorsException::class => EntityConflictException::createResponse(
                'E1542',
                [Localization::trans(QualityService::ERROR_MASSAGE_PLEASE_SOLVE_ERRORS)],
                [
                    'task' => $e->task,
                    'categories' => implode('<br/>', $e->categories),
                ]
            ),
            AttemptToRemoveJobInUseException::class => EntityConflictException::createResponse(
                'E1061',
                [
                    Localization::trans(
                        'The assignment between a task and a user could not be ' .
                        'removed because the user is currently using the task.'
                    ),
                ],
                [
                    'job' => $e->job,
                ]
            ),
            AttemptToRemoveJobWhichTaskIsLockedByUserException::class => EntityConflictException::createResponse(
                'E1062',
                [
                    Localization::trans(
                        'The assignment between a task and a user could not be ' .
                        'removed because the task is currently locked by the user.'
                    ),
                ],
                [
                    'job' => $e->job,
                ]
            ),
            OnlyCoordinatorCanBeAssignedToCoordinatorGroupJobException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'userGuid' => [
                        Localization::trans('Only Coordinator can be assigned to Coordinator group job'),
                    ],
                ],
            ),
            AssignedUserCanBeChangedOnlyForCoordinatorGroupJobException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'userGuid' => [
                        Localization::trans('Assigned user can be changed only for Coordinator group job'),
                    ],
                ],
            ),
            NotCoordinatorGroupCustomerTaskException::class => EntityConflictException::createResponse(
                'E1012',
                [
                    'id' => [
                        Localization::trans('The task does not belong to one of the Coordinator group customers'),
                    ],
                ],
            ),
            CoordinatorDontBelongToLCoordinatorGroupException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'userGuid' => [
                        Localization::trans('Coordinator does not belong to Coordinator group'),
                    ],
                ],
            ),
            AttemptToAssignCoordinatorGroupUserJobBeforeCoordinatorGroupJobCreatedException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'userGuid' => [
                        Localization::trans('Coordinator group user cannot be assigned to a job before the Coordinator group job has been created'),
                    ],
                ],
            ),
            AttemptToAssignSubCoordinatorGroupJobBeforeParentJobCreatedException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'userGuid' => [
                        Localization::trans('Parent Coordinator group does not have appropriate job assignment'),
                    ],
                ],
            ),
            TrackChangesRightsAreNotSubsetOfCoordinatorGroupJobException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'permission' => [
                        Localization::trans('The permissions of the Coordinator group user job should be a subset of the permissions of the Coordinator group job.'),
                    ],
                ],
            ),
            ThereIsUnDeletableBoundJobException::class => EntityConflictException::createResponse(
                'E1162',
                [
                    'id' => [
                        Localization::trans('Coordinator group job has related jobs that cannot be deleted.'),
                    ],
                ],
            ),
            ActionNotAllowedException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'deadlineDate' => [
                        $invalidValueProvidedMessage,
                    ],
                ],
                [
                    'exception' => ActionNotAllowedException::class,
                    'action' => $e->jobAction->value,
                    'job' => $e->userJob->getId(),
                ]
            ),
            ConfirmedCompetitiveJobAlreadyExistsException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'id' => [
                        Localization::trans('Confirmed competitive job assignment already exsists'),
                    ],
                ],
            ),
            CoordinatorGroupJobAlreadyExistsException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'id' => [
                        Localization::trans('Job for current Coordinator group and workflow step already exists.'),
                    ],
                ],
            ),
            CoordinatorHasNotConfirmedCoordinatorGroupJobYetException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'id' => [
                        Localization::trans('Coordinator has not yet confirmed the Coordinator group job'),
                    ],
                ],
            ),
            CoordinatorOfParentGroupHasNotConfirmedCoordinatorGroupJobYetException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'id' => [
                        Localization::trans('The Coordinator of the higher-level Coordinator group has not yet confirmed the Coordinator group job'),
                    ],
                ],
            ),
            JobAlreadyExistsException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'userGuid' => [
                        Localization::trans('The same user cannot be assigned to the same workflow step twice. Please select a different user or a different workflow step.'),
                    ],
                ],
            ),
            default => (function (Throwable $e) {
                $this->logger->exception($e, [
                    'level' => ZfExtended_Logger::LEVEL_DEBUG,
                ]);

                return $e;
            })($e),
        };
    }
}
