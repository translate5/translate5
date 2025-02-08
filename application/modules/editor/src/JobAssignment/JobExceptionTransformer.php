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
use MittagQI\Translate5\JobAssignment\UserJob\Exception\NotCoordinatorGroupCustomerTaskException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\OnlyCoordinatorCanBeAssignedToCoordinatorGroupJobException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\TrackChangesRightsAreNotSubsetOfCoordinatorGroupJobException;
use MittagQI\Translate5\Segment\QualityService;
use MittagQI\Translate5\Task\Exception\InexistentTaskException;
use MittagQI\Translate5\Task\Exception\TaskHasCriticalQualityErrorsException;
use MittagQI\Translate5\User\Exception\InexistentUserException;
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
            'E1280' => 'The format of the segmentrange that is assigned to the user is not valid.',
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
        $invalidValueProvidedMessage = 'Ungültiger Wert bereitgestellt';

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
                    'id' => 'Sie können den Job zur Zeit nicht bearbeiten,'
                        . ' der Benutzer hat die Aufgabe bereits zur Bearbeitung geöffnet.',
                ]
            ),
            InvalidSegmentRangeFormatException::class => UnprocessableEntity::createResponse(
                'E1280',
                [
                    'id' => 'Das Format für die editierbaren Segmente ist nicht valide. Bsp: 1-3,5,8-9',
                ]
            ),
            InvalidSegmentRangeSemanticException::class => UnprocessableEntity::createResponse(
                'E1280',
                [
                    'id' => 'Der Inhalt für die editierbaren Segmente ist nicht valide.'
                        . ' Die Zahlen müssen in der richtigen Reihenfolge angegeben sein und dürfen nicht überlappen,'
                        . ' weder innerhalb der Eingabe noch mit anderen Usern von derselben Rolle.',
                ]
            ),
            TaskHasCriticalQualityErrorsException::class => EntityConflictException::createResponse(
                'E1542',
                [QualityService::ERROR_MASSAGE_PLEASE_SOLVE_ERRORS],
                [
                    'task' => $e->task,
                    'categories' => implode('</br>', $e->categories),
                ]
            ),
            AttemptToRemoveJobInUseException::class => EntityConflictException::createResponse(
                'E1061',
                [
                    'Die Zuweisung zwischen Aufgabe und Benutzer kann nicht gelöscht werden, da der Benutzer diese aktuell benutzt.',
                ],
                [
                    'job' => $e->job,
                ]
            ),
            AttemptToRemoveJobWhichTaskIsLockedByUserException::class => EntityConflictException::createResponse(
                'E1062',
                [
                    'Die Zuweisung zwischen Aufgabe und Benutzer kann nicht gelöscht werden, da die Aufgabe durch den Benutzer gesperrt ist.',
                ],
                [
                    'job' => $e->job,
                ]
            ),
            OnlyCoordinatorCanBeAssignedToCoordinatorGroupJobException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'userGuid' => [
                        'job-assignment.create.only-coordinator-can-be-assigned-to-coordinator-group-job',
                    ],
                ],
            ),
            AssignedUserCanBeChangedOnlyForCoordinatorGroupJobException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'userGuid' => [
                        'job-assignment.update.assigned-user-can-be-changed-only-for-coordinator-group-job',
                    ],
                ],
            ),
            NotCoordinatorGroupCustomerTaskException::class => EntityConflictException::createResponse(
                'E1012',
                [
                    'id' => [
                        'not-coordinator-group-customer-task',
                    ],
                ],
            ),
            CoordinatorDontBelongToLCoordinatorGroupException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'userGuid' => [
                        'coordinator-dont-belong-to-coordinator-group',
                    ],
                ],
            ),
            AttemptToAssignCoordinatorGroupUserJobBeforeCoordinatorGroupJobCreatedException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'userGuid' => [
                        'job-assignment.create.group-user-job-can-not-be-created-before-coordinator-group-job',
                    ],
                ],
            ),
            AttemptToAssignSubCoordinatorGroupJobBeforeParentJobCreatedException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'userGuid' => [
                        'job-assignment.create.parent-coordinator-group-does-not-have-appropriate-job',
                    ],
                ],
            ),
            TrackChangesRightsAreNotSubsetOfCoordinatorGroupJobException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'permission' => [
                        'job-assignment.track-changes-rights-are-not-subset-of-coordinator-group-job',
                    ],
                ],
            ),
            ThereIsUnDeletableBoundJobException::class => EntityConflictException::createResponse(
                'E1162',
                [
                    'id' => [
                        'job-assignment.delete.there-is-un-deletable-bound-job',
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
                        'Es gibt bereits eine bestätigte konkurrierende Auftragsvergabe',
                    ],
                ],
            ),
            CoordinatorGroupJobAlreadyExistsException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'id' => [
                        'job-assignment.create.coordinator-group-job-already-exists',
                    ],
                ],
            ),
            CoordinatorHasNotConfirmedCoordinatorGroupJobYetException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'id' => [
                        'coordinator-has-not-yet-confirmed-the-coordinator-group-job',
                    ],
                ],
            ),
            CoordinatorOfParentGroupHasNotConfirmedCoordinatorGroupJobYetException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'id' => [
                        'coordinator-of-parent-group-has-not-yet-confirmed-the-coordinator-group-job',
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
