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

use editor_Models_Customer_Customer as Customer;
use editor_Models_LanguageResources_CustomerAssoc as LanguageResourceCustomers;
use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Models_LanguageResources_Languages as LanguageResourceLanguages;
use MittagQI\Translate5\LanguageResource\CrossSynchronization\CrossLanguageResourceSynchronizationService;
use MittagQI\Translate5\LanguageResource\CrossSynchronization\CrossSynchronizationConnection;
use MittagQI\Translate5\LanguageResource\CrossSynchronization\SyncConnectionService;
use MittagQI\Translate5\LanguageResource\LanguageResourceRepository;
use MittagQI\Translate5\LanguageResource\TaskAssociation;

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

        $db = $this->entity->db;

        $lrTable = ZfExtended_Factory::get(LanguageResource::class)->db->info($db::NAME);

        $select = $db->select()
            ->setIntegrityCheck(false)
            ->from(
                ['LanguageResourceSync' => $db->info($db::NAME)],
                ['id', 'sourceLanguageResourceId', 'targetLanguageResourceId']
            )
            ->join(
                [
                    'LanguageResourceSource' => $lrTable,
                ],
                'LanguageResourceSync.sourceLanguageResourceId = LanguageResourceSource.id',
                ["CONCAT(LanguageResourceSource.serviceName, ': ', LanguageResourceSource.name) as sourceLanguageResourceName"]
            )
            ->join(
                [
                    'LanguageResourceTarget' => $lrTable,
                ],
                'LanguageResourceSync.targetLanguageResourceId = LanguageResourceTarget.id',
                ["CONCAT(LanguageResourceTarget.serviceName, ': ', LanguageResourceTarget.name) as targetLanguageResourceName"]
            );

        if ($languageResourceId) {
            $select
                ->where('LanguageResourceSync.sourceLanguageResourceId = ?', $languageResourceId)
                ->orWhere('LanguageResourceSync.targetLanguageResourceId = ?', $languageResourceId);
        }

        $rows = $db->fetchAll($select)->toArray();

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

    /***
     * Fire after post/delete special event with language resources service name in it.
     * The event and the service name will be separated with #
     * ex: afterPost#OpenTM2
     *     afterDelete#TermCollection
     *
     * @param string $action
     * @param TaskAssociation $entity
     * @return editor_Models_LanguageResources_LanguageResource
     */
    protected function fireAfterAssocChangeEvent(
        $action,
        TaskAssociation $entity,
    ): editor_Models_LanguageResources_LanguageResource {
        $lr = ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
        /* @var $lr editor_Models_LanguageResources_LanguageResource */
        $lr->load($entity->getLanguageResourceId());

        //fire event with name of the saved language resource service name
        //separate with # so it is more clear that is is not regular after/before action event
        //ex: afterPost#OpenTM2
        $eventName = "after" . ucfirst($action) . '#' . $lr->getServiceName();
        $this->events->trigger($eventName, $this, [
            'entity' => $entity,
        ]);

        return $lr;
    }
}
