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

/***
 * This will send multiple segments at once for translation
 * and save the result in separate table in the database. Later those results will be used for match analysis and pre-translation.
 */
class editor_Plugins_MatchAnalysis_BatchWorker extends editor_Models_Task_AbstractWorker {
    
    /**
     * @var ZfExtended_Logger
     */
    protected ZfExtended_Logger $log;
    
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

        $isRelaisContentField = isset($params['contentField']) && $params['contentField'] === editor_Models_SegmentField::TYPE_RELAIS;

        // if no assoc exist, do not run pretranslations
        if($this->checkTaskAssociation($params['languageResourceId'],$this->taskGuid,$isRelaisContentField) === false){
            // In the case when the worker is queued, but there are no assigments found, do not run the batch worker.
            // This can happen when default user associations and workers will be assigned/queued, but later int the import
            // process the assignments are removed (pivot import from zip files)
            return true;
        }
        
        $manager = ZfExtended_Factory::get('editor_Services_Manager');
        /* @var $manager editor_Services_Manager */

        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var editor_Models_Task $task */
        $task->loadByTaskGuid($this->taskGuid);
        
        $languageResource = ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
        /* @var editor_Models_LanguageResources_LanguageResource $languageResource */
        $languageResource->load($params['languageResourceId']);

        $targetLang = $task->getTargetLang();

        if( $isRelaisContentField){
            $targetLang = $task->getRelaisLang();
        }
        
        $connector = $manager->getConnector($languageResource, $task->getSourceLang(), $targetLang, $task->getConfig());
        /* @var editor_Services_Connector $connector */

        // set the worker user for the connector. This is required for the resource usage log
        $connector->setWorkerUserGuid($params['userGuid']);

        // set the content field for the connector if set as worker argument
        if(isset($params['contentField'])){
            $connector->setAdapterBatchContentField($params['contentField']);
        }

        $connector->batchQuery($this->taskGuid,function($progress){
            //update the worker model progress with progress value reported from the batch query
            $this->updateProgress($progress);
        });
        
        $exceptions = $connector->getBatchExceptions();
        foreach($exceptions as $e) {
            $e->addExtraData(['task' => $task]);
            $this->log->exception($e);
        }
        return true;
    }

    /***
     * Check if given langauge resource is assigned for the current task
     * @param int $resourceId
     * @param string $taskGuid
     * @param bool $isRelaisContentField
     * @return false
     */
    private function checkTaskAssociation(int $resourceId, string $taskGuid,bool $isRelaisContentField){
        $class = $isRelaisContentField ? '\MittagQI\Translate5\LanguageResource\TaskPivotAssociation' : '\MittagQI\Translate5\LanguageResource\TaskAssociation';
        $assoc = ZfExtended_Factory::get($class);
        return $assoc->isAssigned($resourceId,$taskGuid);
    }
    
    /***
     * The batch worker takes approximately 50% of the import time
     * {@inheritDoc}
     * @see ZfExtended_Worker_Abstract::getWeight()
     */
    public function getWeight(): int {
        return 50;
    }
}
