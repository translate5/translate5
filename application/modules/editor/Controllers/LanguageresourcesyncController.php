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
use MittagQI\Translate5\CrossSynchronization\CrossLanguageResourceSynchronizationService;
use MittagQI\Translate5\CrossSynchronization\SynchronisationDirigent;

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

        $options = $resourceSynchronizationService->getAvailableForConnectionOptions($this->entity);

        $this->view->rows = [];

        foreach ($options as $option) {
            $this->view->rows[] = [
                'id' => sprintf(
                    '%s:%s:%s',
                    $option->languageResource->getId(),
                    $option->sourceLanguage->getId(),
                    $option->targetLanguage->getId(),
                ),
                'name' => sprintf(
                    '(%s -> %s) -> %s: %s',
                    $option->sourceLanguage->getRfc5646(),
                    $option->targetLanguage->getRfc5646(),
                    $option->languageResource->getServiceName(),
                    $option->languageResource->getName(),
                ),
            ];
        }
        $this->view->total = count($this->view->rows);
    }

    public function queuesynchronizeallAction(): void
    {
        $this->entityLoad();

        SynchronisationDirigent::create()->queueSynchronizationWhere($this->entity);
    }
}
