<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\LanguageResource\Operation;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Services_Manager as ServiceManager;
use MittagQI\Translate5\LanguageResource\CustomerAssoc\CustomerAssocService;
use MittagQI\Translate5\Repository\LanguageResourceRepository;

class CloneLanguageResourceOperation
{
    public function __construct(
        private readonly CreateLanguagePairOperation $createLanguagePairOperation,
        private readonly CustomerAssocService $customerAssocService,
        private readonly LanguageResourceRepository $languageResourceRepository,
        private readonly ServiceManager $serviceManager,
    ) {
    }

    public static function create(): self
    {
        return new self(
            CreateLanguagePairOperation::create(),
            CustomerAssocService::create(),
            new LanguageResourceRepository(),
            new ServiceManager(),
        );
    }

    public function clone(LanguageResource $languageResource, string $newName): LanguageResource
    {
        $newLanguageResource = new LanguageResource();
        $newLanguageResource->init([
            'resourceId' => $languageResource->getResourceId(),
            'resourceType' => $languageResource->getResourceType(),
            'serviceType' => $languageResource->getServiceType(),
            'serviceName' => $languageResource->getServiceName(),
            'color' => $languageResource->getColor(),
            'name' => $newName,
        ]);
        $newLanguageResource->createLangResUuid();
        $newLanguageResource->validate();
        $this->languageResourceRepository->save($newLanguageResource);

        $this->createLanguagePairOperation->createLanguagePair(
            (int) $newLanguageResource->getId(),
            (int) $languageResource->getSourceLang(),
            (int) $languageResource->getTargetLang()
        );

        foreach ($languageResource->getCustomers() as $customerId) {
            $this->customerAssocService->associate(
                (int) $newLanguageResource->getId(),
                (int) $customerId
            );
        }

        $this->languageResourceRepository->save($newLanguageResource);

        $this->serviceManager->getConnector($newLanguageResource)->addTm();

        $this->serviceManager->getTmConversionService(
            $newLanguageResource->getResource()->getServiceType()
        )?->setRulesHash(
            $newLanguageResource,
            (int) $newLanguageResource->getSourceLang(),
            (int) $newLanguageResource->getTargetLang()
        );

        return $newLanguageResource;
    }
}
