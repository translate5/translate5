<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU
 General Public License version 3.0 as specified by Sencha for Ext Js.
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue,
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3.
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
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
        
        //FIXME errorLog summary hier noch mit reinnehmen!
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
