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

namespace MittagQI\Translate5\JobAssignment\LspJob\DataProvider;

use editor_Models_Task as Task;
use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\JobAssignment\LspJob\Model\Db\LspJobTable;
use MittagQI\Translate5\LSP\JobCoordinatorRepository;
use MittagQI\Translate5\LSP\Model\Db\LanguageServiceProviderTable;
use MittagQI\Translate5\LSP\Model\Db\LanguageServiceProviderUserTable;
use MittagQI\Translate5\Task\ActionAssert\Permission\TaskActionPermissionAssert;
use MittagQI\Translate5\Task\ActionAssert\TaskAction;
use MittagQI\Translate5\User\Model\User;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Table;
use ZfExtended_Factory;

/**
 * @template User as array{userGuid: string, longUserName: string}
 */
class UserProvider
{
    public function __construct(
        private readonly Zend_Db_Adapter_Abstract $db,
        private readonly ActionPermissionAssertInterface $taskActionPermissionAssert,
        private readonly JobCoordinatorRepository $jobCoordinatorRepository,
        private readonly PermissionAwareUserFetcher $permissionAwareUserFetcher,
    ) {
    }

    public static function create(): self
    {
        return new self(
            Zend_Db_Table::getDefaultAdapter(),
            TaskActionPermissionAssert::create(),
            JobCoordinatorRepository::create(),
            PermissionAwareUserFetcher::create(),
        );
    }

    /**
     * @return User[]
     */
    public function getPossibleUsersForNewJobInTask(Task $task, User $viewer): array
    {
        $context = new PermissionAssertContext($viewer);

        if ($viewer->isAdmin()) {
            return array_merge(
                $this->getSimpleUsers($viewer),
                $this->getLspUsers($task->getTaskGuid(), $viewer)
            );
        }

        if (! $this->taskActionPermissionAssert->isGranted(TaskAction::Read, $task, $context)) {
            return [];
        }

        if ($viewer->isPm()) {
            return $this->getSimpleUsers($viewer);
        }

        if (! $viewer->isCoordinator()) {
            return [];
        }

        return $this->getLspUsers($task->getTaskGuid(), $viewer);
    }

    /**
     * @return User[]
     */
    private function getSimpleUsers(User $viewer): array
    {
        $user = ZfExtended_Factory::get(User::class);

        $select = $this->db
            ->select()
            ->from(
                [
                    'user' => $user->db->info($user->db::NAME)
                ]
            )
            ->joinLeft(
                [
                    'lspUser' => LanguageServiceProviderUserTable::TABLE_NAME
                ],
                'lspUser.userId = user.id',
                ['lspUser.lspId']
            )
            ->where('lspUser.lspId IS NULL')
        ;

        return $this->permissionAwareUserFetcher->fetchVisible($select, $viewer);
    }

    /**
     * It is impossible to create LSP User job for LSP User without LSP job
     *
     * @return User[]
     */
    private function getLspUsers(string $taskGuid, User $viewer): array
    {
        $user = ZfExtended_Factory::get(User::class);

        $select = $this->db
            ->select()
            ->distinct()
            ->from(
                [
                    'user' => $user->db->info($user->db::NAME)
                ]
            )
            ->join(
                [
                    'lspUser' => LanguageServiceProviderUserTable::TABLE_NAME
                ],
                'lspUser.userId = user.id',
                []
            )
            ->join(
                [
                    'lsp' => LanguageServiceProviderTable::TABLE_NAME
                ],
                'lspUser.lspId = lsp.id',
                []
            )
            ->join(
                [
                    'lspJob' => LspJobTable::TABLE_NAME
                ],
                'lspJob.lspId = lsp.id',
                []
            )
            ->where('lspJob.taskGuid = ?', $taskGuid)
        ;

        if ($viewer->isCoordinator()) {
            $coordinator = $this->jobCoordinatorRepository->getByUser($viewer);

            $select->where('lsp.id = ?', $coordinator->lsp->getId());
        }

        return $this->permissionAwareUserFetcher->fetchVisible($select, $viewer);
    }
}