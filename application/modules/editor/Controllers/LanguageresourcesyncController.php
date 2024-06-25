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

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use MittagQI\Translate5\LanguageResource\CrossSynchronization\CrossLanguageResourceSynchronizationService;

/**
 * Controller for the LanguageResources Associations
 */
class editor_LanguageresourcesyncController extends ZfExtended_RestController
{
    protected $entityClass = LanguageResource::class;

    /**
     * @var LanguageResource
     */
    protected $entity;

    /**
     * ignoring ID field for POST Requests
     * @var array
     */
    protected $postBlacklist = ['id'];

    public function availableforconnectionAction(): void
    {
        $this->entityLoad();

        $resourceSynchronizationService = CrossLanguageResourceSynchronizationService::create();

        $lrs = $resourceSynchronizationService->getAvailableForConnectionLanguageResources($this->entity);

        $this->view->rows = [];

        foreach ($lrs as $lr) {
            $this->view->rows[] = [
                'id' => $lr->getId(),
                'name' => $lr->getServiceName() . ': ' . $lr->getName(),
            ];
        }
        $this->view->total = count($lrs);
    }

    public function connectavailableAction(): void
    {
        $this->entityLoad();

        $resourceSynchronizationService = CrossLanguageResourceSynchronizationService::create();

        $resourceSynchronizationService->connectAllAvailable($this->entity);
    }
}
