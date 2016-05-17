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

/**
 * Controller for the Plugin TmMtIntegration Associations
 */
class editor_Plugins_TmMtIntegration_TaskassocController extends ZfExtended_RestController {

    protected $entityClass = 'editor_Plugins_TmMtIntegration_Models_TmMtAssocIntegrationMeta';

    /**
     * @var editor_Plugins_TmMtIntegration_Models_TmMtAssocIntegrationMeta
     */
    protected $entity;
    
    /**
     * ignoring ID field for POST Requests
     * @var array
     */
    protected $postBlacklist = array('id');
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::indexAction()
     */
    public function indexAction(){
        $this->view->rows = $this->entity->loadAll();
        $this->view->total = $this->entity->getTotalCount();
    }

    /**
     * for post requests we have to check the existance of the desired task first!
     * (non-PHPdoc)
     * @see ZfExtended_RestController::validate()
     */
    protected function validate() {
        if($this->_request->isPost()) {
            settype($this->data->taskGuid, 'string');
            $t = ZfExtended_Factory::get('editor_Models_Task');
            /* @var $t editor_Models_Task */
            $t->loadByTaskGuid($this->data->taskGuid);
        }
        return parent::validate();
    }

    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::putAction()
     */
    public function putAction() {
      $this->entity->load((int) $this->_getParam('id'));
    }

    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::postAction()
     */
    public function postAction() {
    	$variable = $this->_request;
    	$parametri  = $variable->_params;
        parent::postAction();
        $this->addUserInfoToResult();
    }
    
    /**
     * adds the extended userinfo to the resultset
     */
    protected function addUserInfoToResult() {
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        $user->loadByGuid($this->entity->getUserGuid());
        $this->view->rows->login = $user->getLogin();
        $this->view->rows->firstName = $user->getFirstName();
        $this->view->rows->surName = $user->getSurName();
    }
}