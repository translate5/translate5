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
use MittagQI\Translate5\Repository\UserRepository;
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

    private UserRepository $userRepository;

    private LanguageRepository $languageRepository;

    private LanguageResourceRepository $languageResourceRepository;

    public function init(): void
    {
        parent::init();

        $this->userRepository = new UserRepository();
        $this->languageRepository = LanguageRepository::create();
        $this->languageResourceRepository = new LanguageResourceRepository();
    }

    /**
     * @see ZfExtended_RestController::indexAction()
     */
    public function indexAction()
    {
        $languageResourceId = $this->getRequest()->getParam('languageResource');

        $connectionRepo = new CrossSynchronizationConnectionRepository();
        $integrationManager = new editor_Services_Manager();

        $rows = [];

        foreach ($connectionRepo->getAllConnectionsRenderData((int) $languageResourceId) as $row) {
            $sourceLr = $this->languageResourceRepository->get((int) $row['sourceLanguageResourceId']);
            $targetLr = $this->languageResourceRepository->get((int) $row['targetLanguageResourceId']);
            $connection = $connectionRepo->getConnection((int) $row['id']);

            $additionalInfo = [];

            $additionalInfo[$sourceLr->getName()] = $integrationManager
                ->getSynchronisationService($sourceLr->getServiceType())
                ?->getAdditionalInfoViewData($connection, $sourceLr)
                ->getRows()
            ;
            $additionalInfo[$targetLr->getName()] = $integrationManager
                ->getSynchronisationService($targetLr->getServiceType())
                ?->getAdditionalInfoViewData($connection, $targetLr)
                ->getRows()
            ;

            $rows[] = [
                'id' => $row['id'],
                'sourceLanguageResourceId' => $sourceLr->getId(),
                'targetLanguageResourceId' => $targetLr->getId(),
                'sourceLanguageResourceName' => $sourceLr->getServiceName() . ': ' . $sourceLr->getName(),
                'targetLanguageResourceName' => $targetLr->getServiceName() . ': ' . $targetLr->getName(),
                'sourceLanguage' => $row['sourceLanguage'],
                'targetLanguage' => $row['targetLanguage'],
                'customers' => explode(';', $row['customerNames']),
                'additionalInfo' => array_filter($additionalInfo),
            ];
        }

        /** @phpstan-ignore-next-line */
        $this->view->rows = $rows;
        $this->view->total = count($rows);
    }

    public function postAction(): void
    {
        $this->decodePutData();

        if (empty($this->data['connectionOption'])) {
            throw new MismatchException('E2000', ['connectionOption']);
        }

        $ids = explode(':', $this->data['connectionOption']);

        if (count($ids) !== 3) {
            throw new MismatchException('E2003', ['connectionOption']);
        }

        $authUser = $this->userRepository->get(ZfExtended_Authentication::getInstance()->getUserid());

        [$lrId, $sourceLangId, $targetLangId] = array_map('intval', $ids);

        $source = $this->languageResourceRepository->get((int) ($this->data['sourceLanguageResourceId'] ?? 0));
        $target = $this->languageResourceRepository->get($lrId);

        $sourceLang = $this->languageRepository->get($sourceLangId);
        $targetLang = $this->languageRepository->get($targetLangId);

        CrossLanguageResourceSynchronizationService::create()->connect($source, $target, $sourceLang, $targetLang);

        $this->log->info(
            'E1685',
            'Synchronisation Audit: {message}',
            [
                'message' => sprintf(
                    'User %s connected %s:%s to %s:%s - %s->%s',
                    $authUser->getUsernameLong(),
                    $source->getServiceName(),
                    $source->getName(),
                    $target->getServiceName(),
                    $target->getName(),
                    $sourceLang->getRfc5646(),
                    $targetLang->getRfc5646(),
                ),
                'userUserGuid' => $authUser->getUserGuid(),
                'sourceLanguageResourceId' => $source->getId(),
                'targetLanguageResourceId' => $lrId,
            ]
        );

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

        $authUser = $this->userRepository->get(ZfExtended_Authentication::getInstance()->getUserid());

        $source = $this->languageResourceRepository->get((int) $connection->getSourceLanguageResourceId());
        $target = $this->languageResourceRepository->get((int) $connection->getTargetLanguageResourceId());

        $sourceLanguage = $this->languageRepository->get((int) $connection->getSourceLanguageId());
        $targetLanguage = $this->languageRepository->get((int) $connection->getTargetLanguageId());

        $this->log->info(
            'E1685',
            'Synchronisation Audit: {message}',
            [
                'message' => sprintf(
                    'User %s deleted connection %s:%s to %s:%s - %s->%s',
                    $authUser->getUsernameLong(),
                    $source->getServiceName(),
                    $source->getName(),
                    $target->getServiceName(),
                    $target->getName(),
                    $sourceLanguage->getRfc5646(),
                    $targetLanguage->getRfc5646(),
                ),
                'userUserGuid' => $authUser->getUserGuid(),
                'sourceLanguageResourceId' => $connection->getSourceLanguageResourceId(),
                'targetLanguageResourceId' => $connection->getTargetLanguageResourceId(),
            ]
        );

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

        $authUser = $this->userRepository->get(ZfExtended_Authentication::getInstance()->getUserid());

        $this->log->info(
            'E1685',
            'Synchronisation Audit: {message}',
            [
                'message' => sprintf(
                    'User %s queued synchronisation for connection %s',
                    $authUser->getUsernameLong(),
                    $connection->getId(),
                ),
                'userUserGuid' => $authUser->getUserGuid(),
                'sourceLanguageResourceId' => $connection->getSourceLanguageResourceId(),
                'targetLanguageResourceId' => $connection->getTargetLanguageResourceId(),
            ]
        );

        SynchronisationDirigent::create()->queueConnectionSynchronization($connection);
    }
}
