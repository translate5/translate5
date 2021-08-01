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

namespace Translate5\FrontEndMessageBus;

/**
 * Collector of metrics out of the frontendmessage bus.
 * Colelcts and prints the data.
 */
class Metrics {
    const TYPE_COUNTER = 'counter';
    const TYPE_GAUGE = 'gauge';
    const TYPE_HISTOGRAM = 'histogram';
    const TYPE_SUMMARY = 'summary';
    
    protected $metrics = [];
    
    public function __construct() {
        $this->requestTime = str_replace('.', '', (string) $_SERVER['REQUEST_TIME_FLOAT']);
    }
    
    public function collect(array $instances) {
        $this->metrics = [];
        $total_connections = 0;
        $total_segments_locked = 0;
        $total_task_editing = 0;
        $task_key = 'Translate5\FrontEndMessageBus\Channel\Task';
        
        $this->addMeta('connections', 'Current amount of connections to a translate5 instance', self::TYPE_GAUGE);
        $this->addMeta('segments_locked', 'Current amount of segments locked for editing in a translate5 instance', self::TYPE_GAUGE);
        $this->addMeta('tasks_opened', 'Current amount of tasks opened for editing or viewing in a translate5 instance', self::TYPE_GAUGE);
        
        foreach($instances as $instance) {
            $instance = $instance->debug();
            $this->addMetric('connections', $instance['connectionCount'], [
                'serverHash' => $instance['instance'],
                'serverName' => $instance['instanceName'],
            ]);
            $total_connections += $instance['connectionCount'];
        
            if(!empty($instance['channels'][$task_key])) {
                $count = count($instance['channels'][$task_key]['editedSegments']);
                $this->addMetric('segments_locked', $count, [
                    'serverHash' => $instance['instance'],
                    'serverName' => $instance['instanceName'],
                ]);
                $total_segments_locked += $count;
            }
            
            if(!empty($instance['channels'][$task_key])) {
                $count = count($instance['channels'][$task_key]['taskToSessions']);
                $this->addMetric('tasks_opened', $count, [
                    'serverHash' => $instance['instance'],
                    'serverName' => $instance['instanceName'],
                ]);
                $total_task_editing += $count;
            }
            $this->mergeMetrics($instance);
        }
        
        $this->addMeta('connections_total', 'Current amount of connections to the websocket server', self::TYPE_GAUGE);
        $this->addMetric('connections_total', $total_connections);
        $this->addMeta('segments_locked_total', 'Current amount of segments locked in socket server', self::TYPE_GAUGE);
        $this->addMetric('segments_locked_total', $total_segments_locked);
        $this->addMeta('tasks_opened_total', 'Current amount of tasks opened for editing or viewing in socket server', self::TYPE_GAUGE);
        $this->addMetric('tasks_opened_total', $total_task_editing);
        
        $this->addMeta('memory_usage', 'Memory usages of socket server', self::TYPE_GAUGE);
        $this->addMetric('memory_usage', memory_get_usage(), ['type' => 'current']);
        $this->addMetric('memory_usage', memory_get_usage(true), ['type' => 'current_real']);
        $this->addMetric('memory_usage', memory_get_peak_usage(), ['type' => 'peak']);
        $this->addMetric('memory_usage', memory_get_peak_usage(true), ['type' => 'peak_real']);
    }
    
    protected function addMeta(string $name, string $help, string $type = self::TYPE_COUNTER) {
        // # HELP node_cpu_scaling_frequency_hertz Current scaled cpu thread frequency in hertz.
        // # TYPE node_cpu_scaling_frequency_hertz gauge
        $this->getMetric($name)->help = '# HELP '.$name.' '.$help;
        $this->getMetric($name)->type = '# TYPE '.$name.' '.$type;
    }
    
    /**
     * Merges multiple the metrics
     * @param \stdClass $instance
     */
    protected function mergeMetrics($instance) {
        if(empty($instance['metrics'])) {
            return;
        }
        foreach($instance['metrics'] as $key => $metric) {
            if(empty($this->metrics[$key])) {
                $this->metrics[$key] = clone $metric;
            }
            else {
                $this->metrics[$key]->data = array_merge($this->metrics[$key]->data, $metric->data);
            }
        }
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
            'key' => $name,
            'value' => $value,
            'tags' => $tags,
            'timestamp' => $timestamp
        ];
    }
    
    protected function getMetric($key): \stdClass {
        if(empty($this->metrics[$key])) {
            $this->metrics[$key] = new \stdClass();
            $this->metrics[$key]->data = [];
        }
        return $this->metrics[$key];
    }
    
    public function get() {
        return $this->metrics;
    }
    
    public function __toString() {
        $result = [];
        foreach($this->get() as $key => $metric) {
            if(is_string($metric)) {
                $result[] = $metric;
                continue;
            }
            settype($metric->data, 'array');
            $result[] = $metric->help;
            $result[] = $metric->type;
            foreach($metric->data as $item) {
                settype($item['tags'], 'array');
                //merge tags
                $tags = '';
                if(!empty($item['tags'])) {
                    $tags = '{'.join(',', array_map(function($value, $key){
                        return $key.'="'.$value.'"';
                    }, $item['tags'], array_keys($item['tags']))).'}';
                }
                $result[] = $key.$tags.' '.$item['value'];
            }
        }
        return join("\n", $result);
    }
}
