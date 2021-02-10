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

/***
 * This will send multiple segments at once for translation
 * and save the result in separate table in the database. Later those results will be used for match analysis and pre-translation.
 */
class editor_Plugins_MatchAnalysis_BatchWorker extends ZfExtended_Worker_Abstract {
    
    /**
     * @var ZfExtended_Logger
     */
    protected $log;
    
    public function __construct() {
        parent::__construct();
        $this->log = Zend_Registry::get('logger')->cloneMe('plugin.matchanalysis');
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = array()) {
        return !empty($parameters['languageResourceId']);
    }
    
    /**
     * {@inheritDoc}
     * @see ZfExtended_Worker_Abstract::work()
     */
    public function work() {
        $params = $this->workerModel->getParameters();
        
        $manager = ZfExtended_Factory::get('editor_Services_Manager');
        /* @var $manager editor_Services_Manager */

        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($this->taskGuid);
        
        $languageResource = ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
        /* @var $languageResource editor_Models_LanguageResources_LanguageResource */
        $languageResource->load($params['languageResourceId']);
        
        $connector = $manager->getConnector($languageResource, $task->getSourceLang(), $task->getTargetLang(), $task->getConfig());
        /* @var $connector editor_Services_Connector */
        $connector->batchQuery($this->taskGuid);
        
        $exceptions = $connector->getBatchExceptions();
        foreach($exceptions as $e) {
            $e->addExtraData(['task' => $task]);
            $this->log->exception($e);
        }
        return true;
    }
}
