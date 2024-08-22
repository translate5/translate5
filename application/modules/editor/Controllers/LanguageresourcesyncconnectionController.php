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

use MittagQI\Translate5\CrossSynchronization\CrossLanguageResourceSynchronizationService;
use MittagQI\Translate5\CrossSynchronization\CrossSynchronizationConnection;
use MittagQI\Translate5\CrossSynchronization\SynchronisationDirigent;
use MittagQI\Translate5\Repository\CrossSynchronizationConnectionRepository;
use MittagQI\Translate5\Repository\LanguageRepository;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use MittagQI\ZfExtended\MismatchException;

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

    protected bool $decodePutAssociative = true;

    /**
     * @see ZfExtended_RestController::indexAction()
     */
    public function indexAction()
    {
        $languageResourceId = $this->getRequest()->getParam('languageResource');

        $repo = new CrossSynchronizationConnectionRepository();

        $rows = [];

        foreach ($repo->getAllConnectionsRenderData($languageResourceId) as $row) {
            $id = $row['id'];

            if (! isset($rows[$id])) {
                $rows[$id] = [
                    'id' => $id,
                    'sourceLanguageResourceId' => $row['sourceLanguageResourceId'],
                    'targetLanguageResourceId' => $row['targetLanguageResourceId'],
                    'sourceLanguageResourceName' => $row['sourceServiceName'] . ': ' . $row['sourceName'],
                    'targetLanguageResourceName' => $row['targetServiceName'] . ': ' . $row['targetName'],
                    'sourceLanguage' => $row['sourceLanguage'],
                    'targetLanguage' => $row['targetLanguage'],
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

        $languageRepo = LanguageRepository::create();
        $lrRepo = new LanguageResourceRepository();

        if (empty($this->data['connectionOption'])) {
            throw new MismatchException('E2000', ['connectionOption']);
        }

        $ids = explode(':', $this->data['connectionOption']);

        if (count($ids) !== 3) {
            throw new MismatchException('E2003', ['connectionOption']);
        }

        [$lrId, $sourceLangId, $targetLangId] = array_map('intval', $ids);

        $source = $lrRepo->get((int) ($this->data['sourceLanguageResourceId'] ?? 0));
        $target = $lrRepo->get($lrId);

        $sourceLang = $languageRepo->get($sourceLangId);
        $targetLang = $languageRepo->get($targetLangId);

        CrossLanguageResourceSynchronizationService::create()->connect($source, $target, $sourceLang, $targetLang);

        $this->view->rows = (object) [
            'id' => $source->getId() . ':' . $target->getId(),
            'sourceLanguageResourceId' => $source->getId(),
            'targetLanguageResourceId' => $target->getId(),
            'sourceLanguageResourceName' => $source->getServiceName() . ': ' . $source->getName(),
            'targetLanguageResourceName' => $target->getServiceName() . ': ' . $target->getName(),
            'sourceLanguage' => $sourceLang->getLangName(),
            'targetLanguage' => $targetLang->getLangName(),
        ];
    }

    public function deleteAction(): void
    {
        $syncService = CrossLanguageResourceSynchronizationService::create();
        $connection = $syncService->findConnection((int) $this->_getParam('id'));

        if (null !== $connection) {
            $syncService->deleteConnection($connection);
        }
    }

    public function queuesynchronizeAction(): void
    {
        $connection = CrossLanguageResourceSynchronizationService::create()
            ->findConnection((int) $this->_getParam('id'));

        if ($connection === null) {
            throw new ZfExtended_Models_Entity_NotFoundException();
        }

        SynchronisationDirigent::create()->queueConnectionSynchronization($connection);
    }
}
