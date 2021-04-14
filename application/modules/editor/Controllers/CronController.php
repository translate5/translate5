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
            $msg = "wrong IP to call cronjobs, must be the configured one.\n";
            http_response_code(503);
            echo $msg;
            error_log($msg.' called by: '.$_SERVER['REMOTE_ADDR'].' allowed is: '.$config->runtimeOptions->cronIP);
            exit;
        }
    }
    
    /**
     * Empty index, does nothing
     */
    public function indexAction() {}
    
    /**
     * This action should be called periodically between every 5 to 15 minutes, depending on the traffic on the installation. 
     */
    public function periodicalAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
        /* @var $bootstrap Bootstrap */
        $gc = $bootstrap->getPluginResource('ZfExtended_Resource_GarbageCollector');
        /* @var $gc ZfExtended_Resource_GarbageCollector */
        $gc->cleanUp($gc::ORIGIN_CRON);
        $this->doCronWorkflow('doCronPeriodical');
        echo "OK";
    }
    
    /**
     * triggers daily actions
     */
    public function dailyAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $this->doCronWorkflow('doCronDaily');
        
        $summary = ZfExtended_Factory::get('ZfExtended_Logger_Summary');
        /* @var $summary ZfExtended_Logger_Summary */
        $summary->sendSummaryToAdmins();
        echo "OK";
    }
    
    /**
     * call workflow action based on given name
     * @param string $fn
     */
    protected function doCronWorkflow(string $fn){
        $wfm = ZfExtended_Factory::get('editor_Workflow_Manager');
        /* @var $wfm editor_Workflow_Manager */
        $workflows = $wfm->getWorkflows();
        foreach($workflows as $wfId => $cls) {
            $workflow = $wfm->get($wfId);
            /* @var $workflow editor_Workflow_Abstract */
            $workflow->$fn();
           
        }
    }
}

