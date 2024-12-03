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

namespace MittagQI\Translate5\JobAssignment\LspJob\Operation\WithAuthentication;

use editor_Models_TaskUserAssoc as UserJob;
use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\JobAssignment\LspJob\Contract\DeleteLspJobOperationInterface;
use MittagQI\Translate5\JobAssignment\LspJob\Exception\LspJobAlreadyExistsException;
use MittagQI\Translate5\JobAssignment\LspJob\Model\LspJob;
use MittagQI\Translate5\JobAssignment\UserJob\ActionAssert\Permission\UserJobActionPermissionAssert;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\Repository\UserRepository;
use ZfExtended_Authentication;
use ZfExtended_AuthenticationInterface;

class DeleteLspJobOperation implements DeleteLspJobOperationInterface
{
    /**
     * @param ActionPermissionAssertInterface<UserJob> $permissionAssert
     */
    public function __construct(
        private readonly ZfExtended_AuthenticationInterface $authentication,
        private readonly UserRepository $userRepository,
        private readonly DeleteLspJobOperationInterface $operation,
        private readonly ActionPermissionAssertInterface $permissionAssert,
        private readonly UserJobRepository $userJobRepository,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            ZfExtended_Authentication::getInstance(),
            new UserRepository(),
            \MittagQI\Translate5\JobAssignment\LspJob\Operation\DeleteLspJobOperation::create(),
            UserJobActionPermissionAssert::create(),
            UserJobRepository::create(),
        );
    }

    /**
     * @throws LspJobAlreadyExistsException
     */
    public function delete(LspJob $job): void
    {
        $this->assertAccess($job);

        $this->operation->delete($job);
    }

    public function forceDelete(LspJob $job): void
    {
        $this->assertAccess($job);

        $this->operation->forceDelete($job);
    }

    private function assertAccess(LspJob $job): void
    {
        $authUser = $this->userRepository->get($this->authentication->getUserId());
        $dataJob = $this->userJobRepository->getDataJobByLspJob((int) $job->getId());

        $this->permissionAssert->assertGranted(
            Action::Delete,
            $dataJob,
            new PermissionAssertContext($authUser),
        );
    }
}
