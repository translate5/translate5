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

namespace MittagQI\Translate5\DefaultJobAssignment\DefaultLspJob\Operation\Factory;

use editor_Workflow_Manager;
use MittagQI\Translate5\Customer\Exception\InexistentCustomerException;
use MittagQI\Translate5\DefaultJobAssignment\DefaultUserJob\Operation\DTO\NewDefaultUserJobDto;
use MittagQI\Translate5\DefaultJobAssignment\DTO\TrackChangesRightsDto;
use MittagQI\Translate5\DefaultJobAssignment\DTO\WorkflowDto;
use MittagQI\Translate5\DefaultJobAssignment\Exception\CustomerIdNotProvidedException;
use MittagQI\Translate5\DefaultJobAssignment\Exception\InvalidLanguageIdProvidedException;
use MittagQI\Translate5\DefaultJobAssignment\Exception\InvalidWorkflowStepProvidedException;
use MittagQI\Translate5\DefaultJobAssignment\Exception\SourceLanguageNotProvidedException;
use MittagQI\Translate5\DefaultJobAssignment\Exception\TargetLanguageNotProvidedException;
use MittagQI\Translate5\DefaultJobAssignment\Exception\WorkflowNotProvidedException;
use MittagQI\Translate5\DefaultJobAssignment\Exception\WorkflowStepNotProvidedException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\UserGuidNotProvidedException;
use MittagQI\Translate5\JobAssignment\UserJob\TypeEnum;
use MittagQI\Translate5\Language\LanguageResolver;
use MittagQI\Translate5\Repository\CustomerRepository;
use MittagQI\Translate5\Repository\UserRepository;
use REST_Controller_Request_Http as Request;

class NewDefaultUserJobDtoFactory
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly CustomerRepository $customerRepository,
        private readonly LanguageResolver $languageResolver,
        private readonly editor_Workflow_Manager $workflowManager,
    ) {
    }

    public static function create(): self
    {
        return new self(
            new UserRepository(),
            CustomerRepository::create(),
            LanguageResolver::create(),
            new editor_Workflow_Manager(),
        );
    }

    /**
     * @throws CustomerIdNotProvidedException
     * @throws InexistentCustomerException
     * @throws InvalidLanguageIdProvidedException
     * @throws SourceLanguageNotProvidedException
     * @throws TargetLanguageNotProvidedException
     * @throws WorkflowNotProvidedException
     * @throws WorkflowStepNotProvidedException
     * @throws UserGuidNotProvidedException
     * @throws \editor_Workflow_Exception
     */
    public function fromRequest(Request $request): NewDefaultUserJobDto
    {
        $data = $request->getParam('data');
        $data = json_decode($data, true, flags: JSON_THROW_ON_ERROR);

        $customerId = $request->getParam('customerId');

        if (! isset($data['customerId']) && null === $customerId) {
            throw new CustomerIdNotProvidedException();
        }

        if (! isset($data['userGuid'])) {
            throw new UserGuidNotProvidedException();
        }

        if (! isset($data['sourceLang'])) {
            throw new SourceLanguageNotProvidedException();
        }

        if (! isset($data['targetLang'])) {
            throw new TargetLanguageNotProvidedException();
        }

        if (! isset($data['workflow'])) {
            throw new WorkflowNotProvidedException();
        }

        if (! isset($data['workflowStepName'])) {
            throw new WorkflowStepNotProvidedException();
        }

        $customer = $this->customerRepository->get((int) ($customerId ?: $data['customerId']));
        $user = $this->userRepository->getByGuid($data['userGuid']);

        $sourceLanguage = $this->languageResolver->resolveLanguage($data['sourceLang']);
        $targetLanguage = $this->languageResolver->resolveLanguage($data['targetLang']);

        $workflow = $this->workflowManager->getCached($data['workflow']);

        if (! in_array($data['workflowStepName'], $workflow->getUsableSteps())) {
            throw new InvalidWorkflowStepProvidedException();
        }

        return new NewDefaultUserJobDto(
            (int) $customer->getId(),
            $user->getUserGuid(),
            (int) $sourceLanguage?->getId(),
            (int) $targetLanguage?->getId(),
            new WorkflowDto(
                $workflow->getName(),
                $data['workflowStepName'],
            ),
            TypeEnum::Editor,
            (int) $data['deadlineDate'],
            new TrackChangesRightsDto(
                (bool) ($data['trackchangesShow'] ?? false),
                (bool) ($data['trackchangesShowAll'] ?? false),
                (bool) ($data['trackchangesAcceptReject'] ?? false),
            ),
        );
    }
}