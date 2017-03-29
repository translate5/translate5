<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3
			 http://www.gnu.org/licenses/agpl.html

END LICENSE AND COPYRIGHT
*/

/**
 * Cron Controller 
 * This controller provides methods to be called regularly.
 * Each action should be called through wget by a crontjob  
 */ 
class Editor_CronController extends ZfExtended_Controllers_Action {
    /**
     * @var Zend_Session_Namespace
     */
    protected $session;
    /**
     * @var ZfExtended_Zendoverwrites_Translate
     */
    protected $translate;
    
    /**
     * simple method to secure that controller is only called by the same server (wget)
     * @see ZfExtended_Controllers_Action::init()
     */
    public function init() {
        $config = Zend_Registry::get('config');
        if($config->runtimeOptions->cronIP !== $_SERVER['REMOTE_ADDR']) {
            exit;
        }
    }
    
    /**
     * Empty index, does nothing
     */
    public function indexAction() {}
    
    /**
     * triggers daily actions
     */
    public function dailyAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $wfm = ZfExtended_Factory::get('editor_Workflow_Manager');
        /* @var $wfm editor_Workflow_Manager */
        $workflows = $wfm->getWorkflows();
        foreach($workflows as $wfId => $cls) {
            $workflow = $wfm->get($wfId);
            /* @var $workflow editor_Workflow_Abstract */
            $workflow->doCronDaily();
        }
        echo "OK";
    }
}

