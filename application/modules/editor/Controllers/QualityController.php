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

use MittagQI\Translate5\Task\Current\NoAccessException;
use MittagQI\Translate5\Task\TaskContextTrait;
use MittagQI\ZfExtended\Controller\Response\Header;

/**
 * The Main Quality Controller
 * Provides data for all quality related frontends
 */
class editor_QualityController extends ZfExtended_RestController
{
    use TaskContextTrait;

    /**
     * @var string
     */
    protected $entityClass = 'editor_Models_SegmentQuality';

    /**
     * @var editor_Models_SegmentQuality
     */
    protected $entity;

    /**
     * The download-actions need to be csrf unprotected!
     */
    protected array $_unprotectedActions = ['downloadstatistics'];

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws \MittagQI\Translate5\Task\Current\Exception
     * @throws NoAccessException
     */
    public function init()
    {
        parent::init();
        $this->initCurrentTaskByGuid($this->getRequest()->getParam('taskGuid'), false);
    }

    /**
     * Retrieves all Qualities for the current task as used in the quality filter-panel in the segment grid
     * (non-PHPdoc)
     * @see ZfExtended_RestController::indexAction()
     */
    public function indexAction()
    {
        $task = $this->getCurrentTask();
        $view = new editor_Models_Quality_FilterPanelView($task, true, $this->getRequest()->getParam('currentstate', null));
        $this->view->text = $task->getTaskGuid();
        $this->view->children = $view->getTree();
        $this->view->metaData = $view->getMetaData();
    }

    /**
     * Retrieves the data for the quality statistics download
     */
    public function downloadstatisticsAction()
    {
        $task = $this->getCurrentTask();
        // the field name is unfortunately called "type" in the frontend code
        $field = $this->getRequest()->getParam('type');
        $statisticsProvider = new editor_Models_Quality_StatisticsView($task, $field);

        Header::sendDownload(
            $statisticsProvider->getDownloadName(),
            '"text/xml"; charset="utf8"'
        );

        $this->view->text = $task->getTaskGuid();
        $this->view->children = $statisticsProvider->getTree();
    }

    /**
     * Retrieves the data for the segment's qualities-panel in the segment grid
     */
    public function segmentAction()
    {
        $task = $this->getCurrentTask();
        $segmentId = $this->fetchSegmentId();
        $view = new editor_Models_Quality_SegmentView($task, $segmentId);
        $this->view->rows = $view->getRows();
        $this->view->total = count($this->view->rows);
    }

    /**
     * Spread current value of falsePositive-flag for all other occurrences of such [quality - content] pair found in this task
     */
    public function falsepositivespreadAction()
    {
        $this->entityLoad();
        $this->view->ids = $this->entity->spreadFalsePositive();
        $this->view->success = 1;
    }

    /**
     * Sets the false-positive for a segment
     */
    public function falsepositiveAction()
    {
        $task = $this->getCurrentTask();
        $falsePositive = $this->getRequest()->getParam('falsePositive', null);
        $this->entityLoad();
        $tagAdjusted = true;
        if ($falsePositive !== null && (intval($falsePositive) === 1 || intval($falsePositive) === 0)) {
            // update in quality model
            $this->entity->setFalsePositive(intval($falsePositive));
            $this->entity->save();
            if (editor_Segment_Quality_Manager::instance()->hasSegmentTags($this->entity->getType())) {
                // update tag in segment content
                $tagAdjusted = false;
                $segment = ZfExtended_Factory::get('editor_Models_Segment');
                /* @var $segment editor_Models_Segment */
                $segment->load($this->entity->getSegmentId());
                $fieldTags = $segment->getFieldTags($task, $this->entity->getField());
                if ($fieldTags != null) {
                    $tags = $fieldTags->getByType($this->entity->getType());
                    foreach ($tags as $tag) {
                        if ($tag->getQualityId() == $this->entity->getId()) {
                            $tag->setFalsePositive($this->entity->getFalsePositive());
                            $segment->set($fieldTags->getDataField(), $tag->render());
                            $tagAdjusted = true;

                            break;
                        }
                    }
                }
            }
            $this->view->segmentTagAdjusted = ($tagAdjusted) ? 1 : 0;
            $this->view->success = 1;
        } else {
            ZfExtended_UnprocessableEntity::addCodes([
                'E1025' => 'Field "falsePositive" must be provided.',
            ]);

            throw new ZfExtended_UnprocessableEntity('E1025');
        }
    }

    /**
     * Sets a single qm for a segment
     */
    public function segmentqmAction()
    {
        $task = $this->getCurrentTask();
        $segmentId = $this->getRequest()->getParam('segmentId', null);
        $qmCatIndex = $this->getRequest()->getParam('categoryIndex', null);
        $action = $this->getRequest()->getParam('qmaction', null);
        if ($segmentId != null && $qmCatIndex !== null && ($action == 'add' || $action == 'remove')) {
            /*
             Result looks like
                 $result->qualityId => id of qualities row
                 $result->qualityRow => editor_Models_Db_SegmentQualityRow (only on 'add')
                 $result->success => bool
             */
            $result = editor_Models_Db_SegmentQuality::addOrRemoveQmForSegment($task, intval($segmentId), intval($qmCatIndex), $action);
            if ($result->success) {
                if ($action == 'add') {
                    // data-model must match that of editor_Models_Quality_SegmentView
                    $result->row = editor_Models_Quality_SegmentView::createResultRow(
                        $result->qualityRow,
                        editor_Segment_Quality_Manager::instance(),
                        $task
                    );
                } else {
                    // the removed model needs only an ID ...
                    $result->row = new stdClass();
                    $result->row->id = $result->qualityId;
                }
            }
        } else {
            ZfExtended_UnprocessableEntity::addCodes([
                'E1025' => 'Fields "segmentId", "categoryIndex" and "action" must be provided and valid.',
            ]);

            throw new ZfExtended_UnprocessableEntity('E1025');
        }
        $this->view->success = ($result->success) ? 1 : 0;
        $this->view->row = ($result->success) ? $result->row : null;
        $this->view->action = $action;
    }

    /**
     * Retrieves the data for the qualities-overview of a task in the task info panel
     */
    public function taskAction()
    {
        $task = $this->getCurrentTask();
        $view = new editor_Models_Quality_TaskView($task, true);
        $this->view->text = $task->getTaskGuid();
        $this->view->children = $view->getRows();
        $this->view->metaData = $view->getMetaData();
    }

    /**
     * Retrieves the markup for the qualities tooltip of a task in the task info panel
     */
    public function tasktooltipAction()
    {
        $task = $this->getCurrentTask();
        $toolTip = new editor_Models_Quality_TaskTooltip($task, true);
        echo $toolTip->getMarkup();
    }

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    private function fetchSegmentId(): int
    {
        $segmentId = $this->getRequest()->getParam('segmentId'); //for possiblity to download task outside of editor
        if (is_null($segmentId)) {
            throw new ZfExtended_Models_Entity_NotFoundException('parameter segmentId is required.');
        }

        return intval($segmentId);
    }

    public function putAction()
    {
        throw new ZfExtended_BadMethodCallException(__CLASS__ . '->put');
    }

    public function getAction()
    {
        throw new ZfExtended_BadMethodCallException(__CLASS__ . '->get');
    }

    public function deleteAction()
    {
        throw new ZfExtended_BadMethodCallException(__CLASS__ . '->delete');
    }

    public function postAction()
    {
        throw new ZfExtended_BadMethodCallException(__CLASS__ . '->post');
    }
}
