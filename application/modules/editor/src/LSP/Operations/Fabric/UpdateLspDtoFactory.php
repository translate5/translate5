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

namespace MittagQI\Translate5\LSP\Operations\Fabric;

use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\LSP\Exception\CoordinatorNotFoundException;
use MittagQI\Translate5\LSP\JobCoordinatorRepository;
use MittagQI\Translate5\LSP\Operations\DTO\UpdateLspDto;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\ActionAssert\Permission\UserActionPermissionAssert;
use REST_Controller_Request_Http as Request;
use ZfExtended_Authentication;
use ZfExtended_AuthenticationInterface;

class UpdateLspDtoFactory
{
    public function __construct(
        private readonly ActionPermissionAssertInterface $userPermissionAssert,
        private readonly ZfExtended_AuthenticationInterface $authentication,
        private readonly UserRepository $userRepository,
        private readonly JobCoordinatorRepository $coordinatorRepository,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            UserActionPermissionAssert::create(),
            ZfExtended_Authentication::getInstance(),
            new UserRepository(),
            JobCoordinatorRepository::create(),
        );
    }

    /**
     * @throws CoordinatorNotFoundException
     * @throws \MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface
     * @throws \MittagQI\Translate5\Exception\InexistentUserException
     */
    public function fromRequest(Request $request): UpdateLspDto
    {
        $authUser = $this->userRepository->get($this->authentication->getUserId());

        $data = $request->getParam('data');
        $data = json_decode($data, true, flags: JSON_THROW_ON_ERROR);

        $coordinator = null;

        if (isset($data['notifiableCoordinator'])) {
            try {
                $notifiableUser = $this->userRepository->getByGuid($data['notifiableCoordinator']);

                $this->userPermissionAssert->assertGranted(
                    Action::READ,
                    $notifiableUser,
                    new PermissionAssertContext($authUser)
                );

                $coordinator = $this->coordinatorRepository->getByUser($notifiableUser);
            } catch (\Exception) {
                throw new CoordinatorNotFoundException($data['notifiableCoordinator']);
            }
        }

        return new UpdateLspDto(
            $data['name'] ?? null,
            $data['description'] ?? null,
                $coordinator,
        );
    }
}