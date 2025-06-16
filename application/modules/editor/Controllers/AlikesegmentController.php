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

use MittagQI\Translate5\Repository\SegmentRepository;
use MittagQI\Translate5\Segment\Repetition\DTO\ReplaceDto;
use MittagQI\Translate5\Segment\Repetition\RepetitionService;
use MittagQI\Translate5\Task\Current\NoAccessException;
use MittagQI\Translate5\Task\TaskContextTrait;

/**
 * Editor_AlikeSegmentController
 * Stellt PUT und GET Methoden zur Verarbeitung der Alike Segmente bereit.
 * Ist nicht zu 100% REST konform:
 *  - ein GET auf die Ressource liefert eine Liste mit den Daten für die Anzeige im Alike Editor zurück.
 *  - ein PUT muss eine Liste mit IDs beinhalten, diese IDs werden dann bearbeitet.
 *  - Der PUT liefert eine Liste "rows" mit bearbeiteten, kompletten Segment Daten zu den gegebenen IDs zurück.
 *  - Eine Verortung unter der URL /segment/ID/alikes anstatt alikesegment/ID/ wäre imho sauberer, aber mit Zend REST nicht machbar
 */
class Editor_AlikesegmentController extends ZfExtended_RestController
{
    use TaskContextTrait;

    protected $entityClass = editor_Models_Segment::class;

    /**
     * @var editor_Models_Segment
     */
    protected $entity;

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws \MittagQI\Translate5\Task\Current\Exception
     * @throws NoAccessException
     * @throws ZfExtended_NoAccessException
     */
    public function preDispatch()
    {
        parent::preDispatch();
        $this->initCurrentTask();
    }

    /**
     * lädt das Zielsegment, und übergibt die Alikes zu diesem Segment an die View zur JSON Rückgabe
     * @see ZfExtended_RestController::getAction()
     */
    public function getAction()
    {
        $this->entity->load((int) $this->_getParam('id'));

        $this->view->rows = $this->entity->getAlikes($this->getCurrentTask()->getTaskGuid());
        $this->view->total = count($this->view->rows);
    }

    /**
     * Speichert die Daten des Zielsegments (ID in der URL) in die AlikeSegmente. Die IDs der zu bearbeitenden Alike Segmente werden als Array per PUT übergeben.
     * Die Daten der erfolgreich bearbeiteten Segmente werden vollständig gesammelt und als Array an die View übergeben.
     * @throws NoAccessException
     * @see ZfExtended_RestController::putAction()
     * @deprecated
     */
    public function putAction()
    {
        $task = $this->getCurrentTask();

        /** @var $wfh Editor_Controller_Helper_Workflow */
        $wfh = $this->_helper->workflow; // @phpstan-ignore-line
        $wfh->checkWorkflowWriteable($task->getTaskGuid(), ZfExtended_Authentication::getInstance()->getUserGuid());

        $sfm = editor_Models_SegmentFieldManager::getForTaskGuid($task->getTaskGuid());
        // Only default Layout and therefore no relais can be processed:
        if (! $sfm->isDefaultLayout()) {
            return;
        }

        $this->validateTaskAccess($task->getTaskGuid());

        $segmentRepository = SegmentRepository::create();

        $master = $segmentRepository->get((int) $this->_getParam('id'));

        $ids = $segmentRepository->filterTaskSegmentIds($task->getTaskGuid(), $this->getIds());
        $userJob = $this->getJob($task);

        $repetitionService = RepetitionService::create();

        $replaceDto = new ReplaceDto(
            $task->getTaskGuid(),
            (int) $master->getId(),
            $ids,
            (int) $userJob->getId(),
            (int) $this->_getParam('duration'),
        );

        if ($this->_getParam('async', false)) {
            $repetitionService->queueReplaceBatch($replaceDto);

            $this->view->total = count($ids);

            return;
        }

        $repetitionService->replaceBatch($replaceDto);

        $this->appendTaskProgress($task);

        $rows = [];

        foreach ($segmentRepository->getSegmentsByIds($ids) as $segment) {
            $rows[] = $segment->getDataObject();
        }

        $this->view->rows = $rows;
        $this->view->total = count($rows);
    }

    public function indexAction()
    {
        throw new ZfExtended_BadMethodCallException(__CLASS__ . '->put');
    }

    public function deleteAction()
    {
        throw new ZfExtended_BadMethodCallException(__CLASS__ . '->put');
    }

    public function postAction()
    {
        throw new ZfExtended_BadMethodCallException(__CLASS__ . '->post');
    }

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    private function getJob(editor_Models_Task $task): editor_Models_TaskUserAssoc
    {
        $userGuid = ZfExtended_Authentication::getInstance()->getUserGuid();

        return editor_Models_Loaders_Taskuserassoc::loadByTask($userGuid, $task);
    }

    /**
     * @throws Zend_Json_Exception
     */
    private function getIds(): array
    {
        $ids = array_map(
            'intval',
            (array) Zend_Json::decode($this->_getParam('alikes', "[]"))
        );

        return $ids;
    }
}
