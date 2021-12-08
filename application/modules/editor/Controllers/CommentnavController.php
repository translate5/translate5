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
class Editor_CommentnavController extends ZfExtended_RestController {

    /**
     * @var Zend_Session_Namespace
     */
    protected $session;
    /**
     * @var editor_Models_Task
     */
    protected $task;
    /**
     * @var ZfExtended_Zendoverwrites_Translate
     */
    protected $translate;
    protected $wfAnonymize;

    public function init() {
        $this->initRestControllerSpecific();
    }

/**
 * Loads segment comments in JSON format
 * You can attach listeners to afterIndexAction to add more types.
 * Example:
 * $eventManager->attach('Editor_CommentnavController', 'afterIndexAction, $callback)
 */
    public function indexAction() {
        $this->session = new Zend_Session_Namespace();
        $pluginmanager = Zend_Registry::get('PluginManager');
        $availablePlugins = $pluginmanager->getAvailable();
        $this->task = ZfExtended_Factory::get('editor_Models_Task');
        $this->task->loadByTaskGuid($this->session->taskGuid);
        $this->wfAnonymize = false;
        if($this->task->anonymizeUsers()){
            $this->wfAnonymize = ZfExtended_Factory::get('editor_Workflow_Anonymize');
        }
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $entities = $this->loadSegmentCommentArray();
        if(array_key_exists('VisualReview',$availablePlugins)){
            $annotations = $this->loadAnnotationsArray();
            $entities = array_merge($entities,$annotations);
        }
        $this->view->rows = $entities;
        $this->view->total = count($this->view->rows);
    }

    public function loadSegmentCommentArray(){
        $comment_entity = ZfExtended_Factory::get('editor_Models_Comment');
        $comments = $comment_entity->loadByTaskPlainWithPage($this->session->taskGuid);
        foreach ($comments as &$row) {
            $row['comment'] = htmlspecialchars($row['comment']);
            $row['type'] = 'segmentComment';
            if($this->wfAnonymize) {
                $row = $this->wfAnonymize->anonymizeUserdata($this->session->taskGuid, $row['userGuid'], $row);
            }
        }
        return $comments;
    }

    public function loadAnnotationsArray(){
        $annotation_entity = ZfExtended_Factory::get('editor_Plugins_VisualReview_Annotation_Entity');
        $annotations = $annotation_entity->loadAllByTask($this->session->taskGuid);
        foreach ($annotations as &$row) {
            $row['comment'] = htmlspecialchars($row['text']);
            $row['type'] = 'visualAnnotation';
            if($this->wfAnonymize) {
                $row = $this->wfAnonymize->anonymizeUserdata($this->session->taskGuid, $row['userGuid'], $row);
            }
        }
        return $annotations;
    }

}
