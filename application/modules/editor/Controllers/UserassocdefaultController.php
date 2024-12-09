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

use MittagQI\Translate5\DefaultJobAssignment\DefaultLspJob\Operation\DTO\NewDefaultLspJobDto;
use MittagQI\Translate5\DefaultJobAssignment\DefaultLspJob\Operation\Factory\NewDefaultUserJobDtoFactory;
use MittagQI\Translate5\DefaultJobAssignment\DefaultLspJob\Operation\WithAuthentication\CreateDefaultLspJobOperation;
use MittagQI\Translate5\DefaultJobAssignment\DefaultUserJob\Operation\WithAuthentication\CreateDefaultUserJobOperation;
use MittagQI\Translate5\DefaultJobAssignment\Operation\WithAuthentication\DeleteDefaultJobAssignmentOperation;
use MittagQI\Translate5\JobAssignment\UserJob\TypeEnum;
use MittagQI\Translate5\LSP\JobCoordinatorRepository;
use MittagQI\Translate5\Repository\DefaultUserJobRepository;
use MittagQI\Translate5\Repository\UserRepository;

class Editor_UserassocdefaultController extends ZfExtended_RestController
{
    protected $entityClass = editor_Models_UserAssocDefault::class;

    /**
     * @var editor_Models_UserAssocDefault
     */
    protected $entity;

    /**
     * ignoring ID field for POST Requests
     * @var array
     */
    protected $postBlacklist = ['id'];

    private UserRepository $userRepository;

    public function init(): void
    {
        parent::init();

        $this->userRepository = new UserRepository();
    }

    public function coordinatorscomboAction(): void
    {
        $jobCoordinatorRepository = JobCoordinatorRepository::create();
        $jobCoordinatorRepository->getCoordinatorsCount();
    }

    public function postAction(): void
    {
        parent::postAction();

        return;
        try {
            $authUser = $this->userRepository->get(ZfExtended_Authentication::getInstance()->getUserid());
            $dto = NewDefaultUserJobDtoFactory::create()->fromRequest($this->getRequest());

            if (TypeEnum::Lsp === $dto->type) {
                $lspJob = CreateDefaultLspJobOperation::create()->assignJob(
                    NewDefaultLspJobDto::fromDefaultUserJobDto($dto)
                );
                $userJob = DefaultUserJobRepository::create()->get((int) $lspJob->getDataJobId());
            } else {
                $userJob = CreateDefaultUserJobOperation::create()->assignJob($dto);
            }

            $this->view->rows = (object) $this->userJobViewDataProvider->buildJobView($userJob, $authUser);
        } catch (Throwable $e) {
            $this->log->exception($e);

            throw $this->transformException($e);
        }
    }

    public function deleteAction(): void
    {
        $operation = DeleteDefaultJobAssignmentOperation::create();
        $operation->delete((int) $this->getRequest()->getParam('id'));
    }
}
