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

use MittagQI\Translate5\Models\Task\Current\NoAccessException;
use MittagQI\Translate5\Models\Task\TaskContextTrait;

/**
 * Loads segment comments.
 * Adding further entities can be done via afterIndexAction event.
 * See e.g. editor_Plugins_VisualReview_Init
 */
class Editor_CommentnavController extends ZfExtended_RestController {
    use TaskContextTrait;

    /**
     * @var editor_Workflow_Anonymize
     */
    protected $wfAnonymize;

    const RESTRICTION = "commentnav supports only GET Action";

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws NoAccessException
     * @throws \MittagQI\Translate5\Models\Task\Current\Exception
     */
    public function init() {
        $this->initRestControllerSpecific();
        $this->initCurrentTask();
    }
    
    /**
     * @return editor_Workflow_Anonymize
     */
    public function getWfAnonymize(){
        return $this->wfAnonymize;
    }

    /**
     * Loads segment comments in JSON format
     * You can attach listeners to afterIndexAction to add more types.
     * Example:
     * $eventManager->attach('Editor_CommentnavController', 'afterIndexAction, $callback)
     * @throws \MittagQI\Translate5\Models\Task\Current\Exception
     */
    public function indexAction() {
        $this->wfAnonymize = $this->getCurrentTask()->anonymizeUsers()
                            ? ZfExtended_Factory::get('editor_Workflow_Anonymize')
                            : NULL;
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $this->view->rows = $this->loadSegmentCommentArray();
        $this->view->total = count($this->view->rows);
    }

    /**
     * @throws \MittagQI\Translate5\Models\Task\Current\Exception
     */
    protected function loadSegmentCommentArray(){
        $taskGuid = $this->getCurrentTask()->getTaskGuid();
        $commentEntity = ZfExtended_Factory::get('editor_Models_Comment');
        /* @var $commentEntity editor_Models_Comment */
        $comments = $commentEntity->loadByTaskPlain($taskGuid);
        foreach ($comments as &$row) {
            $row['comment'] = htmlspecialchars($row['comment']);
            $row['type'] = $commentEntity::FRONTEND_ID;
            // the segment mappings segmentPage column  is a Hex-Value and does not qualify for sorting, therefore we add a parsed decimal property
            $this->getWfAnonymize()?->anonymizeUserdata($taskGuid, $row['userGuid'], $row);
        }
        return $comments;
    }

    public function getAction() {
        throw new BadMethodCallException(self::RESTRICTION);
    }

    public function postAction() {
        throw new BadMethodCallException(self::RESTRICTION);
    }
    
    public function putAction() {
        throw new BadMethodCallException(self::RESTRICTION);
    }
    
    public function deleteAction() {
        throw new BadMethodCallException();
    }

}
