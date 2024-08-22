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

namespace MittagQI\Translate5\CrossSynchronization;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Services_Manager;
use MittagQI\Translate5\Repository\CrossSynchronizationConnectionRepository;
use MittagQI\Translate5\Repository\LanguageResourceRepository;

class SynchronisationDirigent
{
    public function __construct(
        private editor_Services_Manager $serviceManager,
        private LanguageResourceRepository $languageResourceRepository,
        private CrossSynchronizationConnectionRepository $connectionRepository,
    ) {
    }

    public static function create(): self
    {
        return new self(
            new editor_Services_Manager(),
            new LanguageResourceRepository(),
            new CrossSynchronizationConnectionRepository(),
        );
    }

    public function queueSynchronizationWhere(LanguageResource $languageResource): void
    {
        $connections = $this->connectionRepository->getConnectionsFor((int) $languageResource->getId());

        foreach ($connections as $connection) {
            $this->queueConnectionSynchronization($connection);
        }
    }

    public function cleanupDefaultSynchronization(LanguageResource $source, LanguageResource $target): void
    {
        $this->serviceManager
            ->getSynchronisationService($target->getServiceType())
            ?->cleanupDefaultSynchronisation($source, $target);
    }

    public function cleanupOnConnectionDeleted(LanguageResource $target, int $customerId): void
    {
        $this->serviceManager
            ->getSynchronisationService($target->getServiceType())
            ?->cleanupOnConnectionDeleted($target, $customerId);
    }

    public function queueDefaultSynchronization(CrossSynchronizationConnection $connection): void
    {
        $target = $this->languageResourceRepository->get((int) $connection->getTargetLanguageResourceId());

        $this->serviceManager
            ->getSynchronisationService($target->getServiceType())
            ?->queueDefaultSynchronisation($connection);
    }

    public function queueConnectionSynchronization(CrossSynchronizationConnection $connection): void
    {
        $target = $this->languageResourceRepository->get((int) $connection->getTargetLanguageResourceId());

        $syncService = $this->serviceManager->getSynchronisationService($target->getServiceType());

        if (null === $syncService) {
            return;
        }

        foreach ($this->connectionRepository->getCustomerAssociations($connection) as $association) {
            $syncService->queueCustomerSynchronisation($connection, (int) $association->getCustomerId());
        }

        $syncService->queueDefaultSynchronisation($connection);
    }

    public function queueCustomerSynchronization(CrossSynchronizationConnection $connection, int $customerId): void
    {
        $target = $this->languageResourceRepository->get((int) $connection->getTargetLanguageResourceId());
        $syncService = $this->serviceManager->getSynchronisationService($target->getServiceType());

        if (null === $syncService) {
            return;
        }

        $syncService->queueCustomerSynchronisation($connection, $customerId);
    }
}
