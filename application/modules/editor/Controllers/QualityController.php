<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

/**
 * The Main Quality Controller
 * Provides data for all quality related frontends
 *
 */
class editor_QualityController extends ZfExtended_RestController {
    
    /**
     * @var string
     */
    protected $entityClass = 'editor_Models_SegmentQuality';
    /**
     * @var editor_Models_SegmentQuality
     */
    protected $entity;
    
    /**
     * Retrieves all Qualities for the current task as used in the quality filter-panel in the segment grid
     * (non-PHPdoc)
     * @see ZfExtended_RestController::indexAction()
     */
    public function indexAction(){
        $task = $this->fetchTask();
        $view = new editor_Models_Quality_FilterPanelView($task, NULL, true, $this->getRequest()->getParam('currentstate', NULL));
        $this->view->text = $task->getTaskGuid();
        $this->view->children = $view->getTree();
        $this->view->metaData = $view->getMetaData();
    }
    /**
     * Retrieves the data for the statistics panel (which is currently not accessible /active)
     */
    public function statisticsAction(){        
        $task = $this->fetchTask();
        $field = $this->getRequest()->getParam('type');
        $statisticsProvider = new editor_Models_Quality_StatisticsView($task, $field);
        $this->view->text = $task->getTaskGuid();
        $this->view->children = $statisticsProvider->getTree();
    }
    /**
     * Retrieves the data for the statistics panel
     */
    public function downloadstatisticsAction(){
        $task = $this->fetchTask();
        $field = $this->getRequest()->getParam('type');
        $statisticsProvider = new editor_Models_Quality_StatisticsView($task, $field);

        header('Content-disposition: attachment; filename="'.$statisticsProvider->getDownloadName().'"');
        header('Content-type: "text/xml"; charset="utf8"', TRUE);
        
        $this->view->text = $task->getTaskGuid();
        $this->view->children = $statisticsProvider->getTree();
    }
    /**
     * Retrieves the data for the segment's qualities-panel in the segment grid
     */
    public function segmentAction(){
        $task = $this->fetchTask();
        $segmentId = $this->fetchSegmentId();
        $view = new editor_Models_Quality_SegmentView($task, $segmentId);
        $this->view->rows = $view->getRows();
        $this->view->total = count($this->view->rows);
    }
    /**
     * Sets the false-positive for a segment
     */
    public function falsepositiveAction(){
        $falsePositive = $this->getRequest()->getParam('falsePositive', NULL);
        $this->entityLoad();
        $tagAdjusted = true;
        if($falsePositive !== NULL && (intval($falsePositive) === 1 || intval($falsePositive) === 0)){
            // update in quality model
            $this->entity->setFalsePositive(intval($falsePositive));
            $this->entity->save();
            if(editor_Segment_Quality_Manager::instance()->hasSegmentTags($this->entity->getType())){
                // update tag in segment content
                $tagAdjusted = false;
                $segment = ZfExtended_Factory::get('editor_Models_Segment');
                /* @var $segment editor_Models_Segment */
                $segment->load($this->entity->getSegmentId());
                $fieldTags = $segment->getFieldTags($this->entity->getField());
                if($fieldTags != NULL){
                    $tags = $fieldTags->getByType($this->entity->getType());
                    foreach($tags as $tag){
                        if($tag->getQualityId() == $this->entity->getId()){
                            $tag->setFalsePositive($this->entity->getFalsePositive());
                            $segment->set($fieldTags->getFirstSaveToField(), $tag->render());
                            $tagAdjusted = true;
                            break;
                        }
                    }
                }
                if(!$tagAdjusted){
                    // TODO AUTOQA: we should write some kind of warning here
                }
            }
            $this->view->segmentTagAdjusted = ($tagAdjusted) ? 1 : 0;
            $this->view->success = 1;
        } else {
            ZfExtended_UnprocessableEntity::addCodes(['E1025' => 'Field "falsePositive" must be provided.']);
            throw new ZfExtended_UnprocessableEntity('E1025');
        }
    }
    /**
     * Sets a single qm for a segment
     */
    public function segmentqmAction(){
        $segmentId = $this->getRequest()->getParam('segmentId', NULL);
        $qmCatIndex = $this->getRequest()->getParam('categoryIndex', NULL);
        $action = $this->getRequest()->getParam('qmaction', NULL);
        if($segmentId != NULL && $qmCatIndex !== NULL && ($action == 'add' || $action == 'remove')){
            $result = editor_Models_Db_SegmentQuality::addOrRemoveQmForSegment($this->fetchTaskGuid(), intval($segmentId), intval($qmCatIndex), $action);            
            // data-model must match that of editor_Models_Quality_SegmentView
            if($result->success == 1 && $action == 'add'){
                // data-model must match that of editor_Models_Quality_SegmentView
                $manager = editor_Segment_Quality_Manager::instance();
                $provider = $manager->getProvider(editor_Segment_Tag::TYPE_QM);
                $result->row->filterable = $manager->isFilterableType(editor_Segment_Tag::TYPE_QM);
                $result->row->falsifiable = $manager->canBeFalsePositiveCategory(editor_Segment_Tag::TYPE_QM, $result->row->category);
                $result->row->hasTag = ($provider != NULL && !$provider->hasSegmentTags());
                $result->row->tagName = ($result->row->hasTag) ? $provider->getTagNodeName() : '';
                $result->row->cssClass = ($result->row->hasTag) ? $provider->getTagIndentificationClass() : '';
            }
        } else {
            ZfExtended_UnprocessableEntity::addCodes(['E1025' => 'Fields "segmentId", "categoryIndex" and "action" must be provided and valid.']);
            throw new ZfExtended_UnprocessableEntity('E1025');
        }
        $this->view->success = $result->success;
        $this->view->row = ($result->success == 1) ? $result->row : NULL;
        $this->view->action = $action;
    }
    /** 
     * Retrieves the data for the qualities-overview of a task in the task info panel
     */
    public function taskAction(){
        $task = $this->fetchTask();
        $view = new editor_Models_Quality_TaskView($task, NULL, true);
        $this->view->rows = $view->getRows();
        $this->view->total = count($this->view->rows);
        $this->view->metaData = $view->getMetaData();
    }
    /**
     * Retrieves the data for the qualities tooltip of a task in the task info panel
     * TODO AUTOQA: REMOVE ?
     */
    public function tasktooltipAction(){
        $this->taskAction();
    }
    /**
     * @throws ZfExtended_Exception
     * @return editor_Models_Task
     */
    private function fetchTask() : editor_Models_Task {
        $taskGuid = $this->fetchTaskGuid();
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);
        return $task;
    }
    /**
     *
     * @throws ZfExtended_NotAuthenticatedException
     * @return string
     */
    private function fetchTaskGuid() : string {
        $taskGuid = $this->getRequest()->getParam('taskGuid'); //for possiblity to download task outside of editor
        if(is_null($taskGuid)){
            $session = new Zend_Session_Namespace();
            $taskGuid = $session->taskGuid;
        }
        if(empty($taskGuid)){
            throw new ZfExtended_NotAuthenticatedException();
        }
        return $taskGuid;
    }
    /**
     *
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @return int
     */
    private function fetchSegmentId() : int {
        $segmentId = $this->getRequest()->getParam('segmentId'); //for possiblity to download task outside of editor
        if(is_null($segmentId)){
            throw new ZfExtended_Models_Entity_NotFoundException('parameter segmentId is required.');
        }
        return intval($segmentId);
    }
    
    public function putAction(){
        throw new ZfExtended_BadMethodCallException(__CLASS__.'->put');
    }

    public function getAction(){
        throw new ZfExtended_BadMethodCallException(__CLASS__.'->get');
    }
    
    public function deleteAction(){
        throw new ZfExtended_BadMethodCallException(__CLASS__.'->delete');
    }

    public function postAction(){
        throw new ZfExtended_BadMethodCallException(__CLASS__.'->post');
    }
}