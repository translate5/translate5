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

use MittagQI\Translate5\LanguageResource\TaskAssociation;
use MittagQI\Translate5\Segment\FilteredIterator;
use MittagQI\Translate5\Test\Filter;

/**
 * Reimports the segments of a task back into the chosen TM
 */
class editor_Models_LanguageResources_Worker extends editor_Models_Task_AbstractWorker {

    const STATE_REIMPORT = 'reimporttm';

    private string $oldState;

    private editor_Models_LanguageResources_LanguageResource $languageresource;

    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = array()) {
        if(empty($parameters['languageResourceId'])) {
            return false;
        }
        return true;
    } 
    
    /**
     * {@inheritDoc}
     * @see ZfExtended_Worker_Abstract::work()
     */
    public function work() {
        $params = $this->workerModel->getParameters();

        $this->languageresource = ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
        $this->languageresource->load($params['languageResourceId']);

        $task = $this->task;
        if(!$task->lock(NOW_ISO, self::STATE_REIMPORT)) {
            $this->getLogger()->error('E1169', 'The task is in use and cannot be reimported into the associated language resources.');
            return false;
        }
        $this->oldState = $task->getState();
        $task->setState(self::STATE_REIMPORT);
        $task->save();
        $task->createMaterializedView();

        $segments = $this->getSegmentInterator($task,$params);

        $this->updateSegments($segments);

        $this->reopenTask();
        $this->getLogger()->info('E0000', 'Task reimported successfully into the desired TM');
        return true;
    }

    /**
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    protected function reopenTask() {
        $this->task->setState($this->oldState);
        $this->task->save();
        if($this->oldState == $this->task::STATE_END) {
            $this->task->dropMaterializedView();
        }
        $this->task->unlock();
    }

    /**
     * @param Throwable $workException
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    protected function handleWorkerException(Throwable $workException) {
        $this->reopenTask();
        $this->getLogger()->error('E0000', 'Task reimport in TM failed - please check log for reason and restart!');
        if($workException instanceof ZfExtended_ErrorCodeException) {
            $workException->addExtraData(['languageResource' => $this->languageresource]);
        }
        parent::handleWorkerException($workException);
    }

    /**
     * @return ZfExtended_Logger
     * @throws Zend_Exception
     */
    private function getLogger(): ZfExtended_Logger {
        return Zend_Registry::get('logger')->cloneMe('editor.languageresource', [
            'task' => $this->task ?? null,
            'languageResource' => $this->languageresource ?? null,
        ]);
    }

    /***
     * Get the segment iterator type based on worker params.
     * @param editor_Models_Task $task
     * @param array $params
     * @return editor_Models_Segment_Iterator
     */
    private function getSegmentInterator(editor_Models_Task $task, array $params): editor_Models_Segment_Iterator
    {
        if( !isset($params['segmentFilter'])){
            return ZfExtended_Factory::get('editor_Models_Segment_Iterator', [$task->getTaskGuid()]);
            /* @var editor_Models_Segment_Iterator $segments */
        }
        // if segment filter param is set, add timestamp filter to the iterator so all loaded segments are filtered
        // by the given timestamp
        $filterObject = new stdClass();
        $filterObject->field = 'timestamp';
        $filterObject->type = 'string';
        $filterObject->comparison = 'eq';
        $filterObject->value = $params['segmentFilter'];

        /** @var editor_Models_Segment $segment */
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        $filter = ZfExtended_Factory::get('ZfExtended_Models_Filter_ExtJs6', array(
            $segment
        ));
        $segment->filterAndSort($filter);

        $segment->getFilter()->addFilter($filterObject);

        /** @var Filter $segments */
         return ZfExtended_Factory::get(FilteredIterator::class,[
            $task->getTaskGuid(),
            $segment
        ]);
    }

    /**
     * Update the current langauge resource with all filtered segments
     *
     * @param editor_Models_Segment_Iterator $segments
     * @return void
     * @throws ZfExtended_Exception
     */
    public function updateSegments(editor_Models_Segment_Iterator $segments): void
    {

        // in case of filtered segments, the initialization of the segments iterator can result with no segments found for the applied filter
        if($segments->isEmpty()){
            return;
        }

        $assoc = ZfExtended_Factory::get(TaskAssociation::class);
        /* @var MittagQI\Translate5\LanguageResource\TaskAssociation $assoc */

        $assoc->loadByTaskGuidAndTm($this->task->getTaskGuid(), $this->workerModel->getParameters()['languageResourceId']);

        $manager = ZfExtended_Factory::get('editor_Services_Manager');
        /* @var editor_Services_Manager $manager */

        $connector = $manager->getConnector($this->languageresource,null,null,$this->task->getConfig());

        foreach ($segments as $segment) {
            if (empty($segment->getTargetEdit()) || str_contains($segment->getTargetEdit(), "\n")) {
                continue;
            }
            // check if the current langauge resources is updatable before updating
            if (!empty($assoc->getSegmentsUpdateable())) {
                try {
                    $connector->update($segment);
                } catch (ZfExtended_Zendoverwrites_Http_Exception|editor_Services_Connector_Exception) {
                    //if the TM is not available (due service restart or whatever) we just wait some time and try it again once.
                    sleep(30);
                    $connector->update($segment);
                }
            }
        }
    }
}
