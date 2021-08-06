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

class Editor_SegmentuserassocController extends editor_Controllers_EditorrestController {

    protected $entityClass = 'editor_Models_SegmentUserAssoc';
    
    /**
     * @var editor_Models_SegmentUserAssoc
     */
    protected $entity;
    
    public function deleteAction() {
        $id = (int) $this->_getParam('id');
        try {
            $this->entity->load($id);
            $this->entity->delete();
        }
        catch (ZfExtended_Models_Entity_NotFoundException $e) {
            //do nothing, since already deleted!
        }
    }
    
    public function postAction() {
        $session = new Zend_Session_Namespace();
        $sessionUser = new Zend_Session_Namespace('user');
        $userGuid = $sessionUser->data->userGuid;
        $now = date('Y-m-d H:i:s');
        $this->entity->init();
        $this->entity->setModified($now);
        $this->entity->setCreated($now);
        $this->entity->setTaskGuid($session->taskGuid);
        $this->entity->setUserGuid($userGuid);
        $this->decodePutData();
        $this->checkSegmentTaskGuid($this->data->segmentId);
        $this->entity->setSegmentId($this->data->segmentId);
        $this->entity->validate();
        try {
            $this->entity->save();
        }
        catch (ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey $e) {
            // on duplicate key everything is ok, the entry is already existing
        }
        $this->view->rows = $this->entity->getDataObject();
    }
    
    /**
     * compares the taskGuid of the desired segment and the actually loaded taskGuid
     * @param int $segmentId
     * @throws ZfExtended_Models_Entity_NoAccessException
     */
    protected function checkSegmentTaskGuid(int $segmentId) {
        $session = new Zend_Session_Namespace();
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        $segment->load($segmentId);
        if ($session->taskGuid !== $segment->getTaskGuid()) {
            throw new ZfExtended_Models_Entity_NoAccessException();
        }
    }
}