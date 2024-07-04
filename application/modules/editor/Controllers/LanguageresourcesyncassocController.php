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
use MittagQI\Translate5\LanguageResource\CrossSynchronization\CrossSynchronizationConnectionRepository;
use MittagQI\Translate5\LanguageResource\LanguageResourceRepository;

/**
 * Controller for the LanguageResources Associations
 */
class editor_LanguageresourcesyncassocController extends ZfExtended_RestController
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

    public function init()
    {
        ZfExtended_Models_Entity_Conflict::addCodes([
            'E1050' => 'Referenced language resource not found.',
            'E1051' => 'Cannot remove language resource from task since task is used at the moment.',
        ], 'editor.languageresource.taskassoc');
        parent::init();
    }

    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::indexAction()
     */
    public function indexAction()
    {
        $languageResourceId = $this->getRequest()->getParam('languageResource');

        $repo = new CrossSynchronizationConnectionRepository();

        $rows = [];

        foreach ($repo->getAllConnectionsRenderData($languageResourceId) as $row) {
            $rows[] = [
                'id' => $row['id'],
                'sourceLanguageResourceId' => $row['sourceLanguageResourceId'],
                'targetLanguageResourceId' => $row['targetLanguageResourceId'],
                'sourceLanguageResourceName' => $row['sourceName'] . ': ' . $row['sourceName'],
                'targetLanguageResourceName' => $row['targetServiceName'] . ': ' . $row['targetName'],
            ];
        }

        $this->view->rows = $rows;
        $this->view->total = count($rows);
    }

    public function postAction(): void
    {
        $this->decodePutData();

        $lrRepo = new LanguageResourceRepository();

        $sourceLanguageResource = $lrRepo->get((int) $this->data->sourceLanguageResourceId ?: 0);
        $targetLanguageResource = $lrRepo->get((int) $this->data->targetLanguageResourceId ?: 0);

        $connection = CrossLanguageResourceSynchronizationService::create()->createConnection(
            $sourceLanguageResource,
            $targetLanguageResource,
        );

        $this->view->rows = $connection->toArray();
    }

    public function deleteAction()
    {
        try {
            $this->entityLoad();

            CrossLanguageResourceSynchronizationService::create()->deleteConnection($this->entity);
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
            //do nothing since it was already deleted, and thats ok since user tried to delete it
        }
    }
}
