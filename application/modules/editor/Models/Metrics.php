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

/**
 * Collector of application metrics in a form to be given to socket server
 */
class editor_Models_Metrics {
    const TYPE_COUNTER = 'counter';
    const TYPE_GAUGE = 'gauge';
    const TYPE_HISTOGRAM = 'histogram';
    const TYPE_SUMMARY = 'summary';
    
    protected $metrics = [];
    
    public function __construct() {
        $this->serverDir = explode(DIRECTORY_SEPARATOR, trim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR));
        array_pop($this->serverDir); //remove public
        $this->serverDir = array_pop($this->serverDir);
        //or better REQUEST_TIME_FLOAT?
        $this->requestTime = str_replace('.', '', (string) $_SERVER['REQUEST_TIME_FLOAT']);
    }
    
    public function collect() {
        $this->worker();
        $this->tasks();
        $this->jobs();
        $this->eventlogs();
    }
    
    protected function jobs() {
        $task = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $task editor_Models_TaskUserAssoc */
        $this->addMeta('jobs_total', 'Current amount of jobs', self::TYPE_GAUGE);
        $summary = $task->getSummary();
        foreach ($summary as $count) {
            $this->addMetric('jobs_total', (int) $count['jobCount'], [
                'state' => $count['state'],
                'role' => $count['role'],
                'usedstate' => $count['usedstate'],
            ]);
        }
    }
    
    protected function tasks() {
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $this->addMeta('tasks_total', 'Current amount of tasks', self::TYPE_GAUGE);
        $this->addMeta('task_words_total', 'Current amount of words in tasks', self::TYPE_GAUGE);
        $this->addMeta('task_segments_total', 'Current amount of segments in tasks', self::TYPE_GAUGE);
        $summary = $task->getSummary();
        foreach ($summary as $count) {
            $this->addMetric('tasks_total', (int) $count['taskCount'], ['state' => $count['state'], 'type' => $count['taskType']]);
            $this->addMetric('task_words_total', (int) $count['wordCountSum'], ['state' => $count['state'], 'type' => $count['taskType']]);
            $this->addMetric('task_segments_total', (int) $count['segmentCountSum'], ['state' => $count['state'], 'type' => $count['taskType']]);
        }
    }
    
    protected function worker() {
        $worker = ZfExtended_Factory::get('ZfExtended_Models_Worker');
        /* @var $worker ZfExtended_Models_Worker */
        $summary = $worker->getSummary(['state', 'worker']);
        $this->addMeta('worker_total', 'Current amount of workers', self::TYPE_GAUGE);
        foreach ($summary as $count) {
            $this->addMetric('worker_total', (int) $count['cnt'], ['state' => $count['state'], 'worker' => $count['worker']]);
        }
    }
    
    protected function eventlogs() {
        $db = ZfExtended_Factory::get('ZfExtended_Models_Db_ErrorLog');
        /* @var $db ZfExtended_Models_Db_ErrorLog */

        $logger = Zend_Registry::get('logger');
        /* @var $logger ZfExtended_Logger */
        
        $s = $db->select()
        ->from($db, ['cnt' => 'count(*)', 'level'])
        ->group('level');
        
        $res = $db->fetchAll($s);
        if(empty($res)) {
            return;
        }
        
        $this->addMeta('event_log_count', 'Current amount of event log entries', self::TYPE_GAUGE);
        foreach ($res as $count) {
            $this->addMetric('event_log_count', (int) $count['cnt'], ['level' => $logger->getLevelName($count['level'])]);
        }
    }
    
    /**
     * Adds the meta information like help and type string
     * @param string $name
     * @param string $help
     * @param string $type
     */
    protected function addMeta(string $name, string $help, string $type = self::TYPE_COUNTER) {
        // # HELP node_cpu_scaling_frequency_hertz Current scaled cpu thread frequency in hertz.
        // # TYPE node_cpu_scaling_frequency_hertz gauge
        $this->getMetric($name)->help = '# HELP '.$name.' '.$help;
        $this->getMetric($name)->type = '# TYPE '.$name.' '.$type;
    }
    
    /**
     * Adds a metric dataset
     * @param string $name
     * @param string $value
     * @param array $tags
     * @param string $timestamp
     */
    protected function addMetric(string $name, string $value, array $tags = [], string $timestamp = null) {
        $this->getMetric($name)->data[] = [
            'value' => $value,
            'tags' => $tags,
            'timestamp' => $timestamp
        ];
    }
    
    protected function getMetric($key): stdClass {
        if(empty($this->metrics[$key])) {
            $this->metrics[$key] = new stdClass();
            $this->metrics[$key]->data = [];
        }
        return $this->metrics[$key];
    }
    
    public function get() {
        return $this->metrics;
    }
    
    public function __toString() {
        $this->collect();
        return print_r($this->metrics, 1);
    }
}
