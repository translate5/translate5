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

class Editor_CommentnavController extends ZfExtended_Controllers_Action {

    /**
     * @var Zend_Session_Namespace
     */
    protected $session;
    /**
     * @var ZfExtended_Zendoverwrites_Translate
     */
    protected $translate;

    public function init() {
        parent::init();
        //$restContexts = Zend_Controller_Action_HelperBroker::getExistingHelper('restContexts');

        $contextSwitch = Zend_Controller_Action_HelperBroker::getExistingHelper('contextSwitch');
        $contextSwitch->setAutoSerialization(true);
        $contextSwitch->initContext('json');
        $contextSwitch->addActionContext('index', true);

       //foreach ($restContexts->getControllerActions($controller) as $action) {
            //$contextSwitch->addActionContext($action, true);
        //}
    }

    public function indexAction() {
        $this->session = new Zend_Session_Namespace();
        $task = ZfExtended_Factory::get('editor_Models_Task');
        $task->loadByTaskGuid($this->session->taskGuid);
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $comment_entity = ZfExtended_Factory::get('editor_Models_Comment');
        //$annotation_entity = ZfExtended_Factory::get('editor_Models_Comment');

        $this->view->rows = $comment_entity->loadByTaskPlain($this->session->taskGuid);
        $wfAnonymize = false;
        if($task->anonymizeUsers()){
            $wfAnonymize = ZfExtended_Factory::get('editor_Workflow_Anonymize');
        }
        foreach ($this->view->rows as &$row) {
            $row['comment'] = htmlspecialchars($row['comment']);
            $row['type'] = 'segmentComment';
            unset($row['userGuid']);
            if($wfAnonymize) {
                $row = $wfAnonymize->anonymizeUserdata($this->session->taskGuid, $row['userGuid'], $row);
            }

        }
        $this->view->total = count($this->view->rows);
    }

}
