<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
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
        $id = (int) $this->_getParam('segmentUserAssocId');
        $this->entity->load($id);
        $this->entity->delete();
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
        $this->entity->validate();
        $this->entity->save();
        $this->view->rows = $this->entity->getDataObject();
    }
}