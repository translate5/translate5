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

use MittagQI\Translate5\LanguageResource\CrossSynchronization\CrossLanguageResourceSynchronizationService;
use MittagQI\Translate5\LanguageResource\CrossSynchronization\CrossSynchronizationConnection;
use MittagQI\Translate5\LanguageResource\CrossSynchronization\SynchronisationDirigent;
use MittagQI\Translate5\Repository\CrossSynchronizationConnectionRepository;
use MittagQI\Translate5\Repository\LanguageResourceRepository;

/**
 * Controller for the LanguageResources Associations
 */
class editor_LanguageresourcesyncconnectionController extends ZfExtended_RestController
{
    protected $entityClass = CrossSynchronizationConnection::class;

    /**
     * @var CrossSynchronizationConnection
     */
    protected $entity;

    /**
     * ignoring ID field for POST Requests
     * @var array
     */
    protected $postBlacklist = ['id'];

    /**
     * @see ZfExtended_RestController::indexAction()
     */
    public function indexAction()
    {
        $languageResourceId = $this->getRequest()->getParam('languageResource');

        $repo = new CrossSynchronizationConnectionRepository();

        $rows = [];

        foreach ($repo->getAllConnectionsRenderData($languageResourceId) as $row) {
            $id = $row['sourceLanguageResourceId'] . ':' . $row['targetLanguageResourceId'];

            if (! isset($rows[$id])) {
                $rows[$id] = [
                    'id' => $row['sourceLanguageResourceId'] . ':' . $row['targetLanguageResourceId'],
                    'sourceLanguageResourceId' => $row['sourceLanguageResourceId'],
                    'targetLanguageResourceId' => $row['targetLanguageResourceId'],
                    'sourceLanguageResourceName' => $row['sourceServiceName'] . ': ' . $row['sourceName'],
                    'targetLanguageResourceName' => $row['targetServiceName'] . ': ' . $row['targetName'],
                    'customers' => [],
                ];
            }

            $rows[$id]['customers'][] = $row['customerName'];
        }

        /** @phpstan-ignore-next-line  */
        $this->view->rows = array_values($rows);
        $this->view->total = count($rows);
    }

    public function postAction(): void
    {
        $this->decodePutData();

        $lrRepo = new LanguageResourceRepository();

        $source = $lrRepo->get((int) $this->data->sourceLanguageResourceId ?: 0);
        $target = $lrRepo->get((int) $this->data->targetLanguageResourceId ?: 0);

        CrossLanguageResourceSynchronizationService::create()->connect($source, $target);

        $this->view->rows = (object) [
            'id' => $source->getId() . ':' . $target->getId(),
            'sourceLanguageResourceId' => $source->getId(),
            'targetLanguageResourceId' => $target->getId(),
            'sourceLanguageResourceName' => $source->getServiceName() . ': ' . $source->getName(),
            'targetLanguageResourceName' => $target->getServiceName() . ': ' . $target->getName(),
        ];
    }

    public function deleteAction(): void
    {
        $lrRepo = new LanguageResourceRepository();
        $connectedIds = explode(':', $this->_getParam('id'));

        try {
            $source = $lrRepo->get((int) $connectedIds[0]);
            $target = $lrRepo->get((int) $connectedIds[1]);

            CrossLanguageResourceSynchronizationService::create()->deleteConnections($source, $target);
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            //do nothing since it was already deleted, and that is ok since user tried to delete it
        }
    }

    public function queuesynchronizeAction(): void
    {
        $connectedIds = explode(':', $this->_getParam('id'));
        SynchronisationDirigent::create()
            ->queueSynchronizationForPair((int) $connectedIds[0], (int) $connectedIds[1]);
    }
}
