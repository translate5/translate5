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
 * Base class for mass data generators of qualities, basically aggregates the qualities for segments
 * Accessing the aggregated data is generally per segmentId
 * This is designed for export or other prposes and thus uses more "traditional sql" instead of expensive Zend Objects
 * It is expected that there are 3 filters: Task-GUID, Segment Ids (optional), quality types (optional) and if to exclude false positives
 * This class works generally by setting the filter on instantiation (which fetches the data) and to retrieve the fetched data per segment
 * Default filters can be set with the applyDefaults method
 */
abstract class editor_Models_Quality_AbstractData {
    
    /**
     * 
     * @var editor_Models_Task
     */
    protected $task;
    /**
     * 
     * @var int[]
     */
    protected $segmentIds = [];
    /**
     * 
     * @var string[]
     */
    protected $types = [];
    /**
     *
     * @var string[]
     */
    protected $fields = [];
    /**
     * 
     * @var boolean
     */
    protected $excludeFalsePositives = false;
    /**
     * 
     * @var array: segmentId => qualities
     */
    protected $data = [];
    /**
     * These columns set here MUST be included !
     * id, taskGuid, segmentId, field, type, category, startIndex, endIndex, falsePositive, additionalData, categoryIndex, severity, comment
     * @var array
     */
    protected $columnsToFetch = ['id', 'segmentId', 'type', 'category'];
    /**
     * If set, Translations will be added as column 'text' to the results
     * @var boolean
     */
    protected $addTranslations = false;
    /**
     * The seperator to seperate between translated type & translated category
     * @var string
     */
    protected $translationSeperator = ' > ';
    /**
     * 
     * @var editor_Segment_Quality_Manager
     */    
    protected $manager;

    /**
     * 
     * @param editor_Models_Task $task
     * @param array $segmentIds
     * @param array $types
     * @param array $fields
     * @param bool $withFalsePositives
     */
    public function __construct(editor_Models_Task $task, array $segmentIds=NULL, array $types=NULL, array $fields=NULL, bool $withFalsePositives=NULL){
        $this->task = $task;
        $this->applyDefaults();
        if(is_array($segmentIds)){
            $this->segmentIds = $segmentIds;
        }
        if(is_array($types)){
            $this->types = $types;
        }
        if(is_bool($withFalsePositives)){
            $this->excludeFalsePositives = ($withFalsePositives == false);
        }
        if($this->addTranslations){
            $this->manager = editor_Segment_Quality_Manager::instance();
        }
        $this->fetch();
    }
    /**
     * This method can be overwritten in extending classes to set default filters or other props (which may be overwritten on instantiation
     */
    protected function applyDefaults(){
        
    }
    /**
     * This method can be overwritten to transform the data for a single quality into something else
     * @param array $qualityData: the raw data for a single quality
     * @return array|string|int|bool
     */
    protected function transformRow(array $qualityData){
        return $qualityData;
    }
    /**
     * This method can be overwritten to transform the segments aggregated data into something else
     * @param array $segmentData
     * @return array
     */
    protected function transformData(array $segmentData) : array {
        return $segmentData;
    }
    /**
     * fills the raw data
     */
    protected function fetch() {
        
        $table = ZfExtended_Factory::get('editor_Models_Db_SegmentQuality');
        /* @var $table editor_Models_Db_SegmentQuality */
        $db = $table->getAdapter();
        $sql =
            'SELECT `'.implode('`,`', $this->columnsToFetch).'` FROM '.$db->quoteIdentifier($table->getName()).' '
            .$db->quoteInto('WHERE taskGuid = ?', $this->task->getTaskGuid());
        if(count($this->segmentIds) > 0){
            $sql .= ' AND '.((count($this->segmentIds) > 1) ? $db->quoteInto('segmentId IN (?)', $this->segmentIds, Zend_Db::INT_TYPE) : $db->quoteInto('segmentId = ?', $this->segmentIds[0], Zend_Db::INT_TYPE));
        }
        if(count($this->types) > 0){
            $sql .= ' AND '.((count($this->types) > 1) ? $db->quoteInto('type IN (?)', $this->types) : $db->quoteInto('type = ?', $this->types[0]));
        }
        if(count($this->fields) > 0){
            $sql .= ' AND ('.((count($this->fields) > 1) ? $db->quoteInto('field IN (?)', $this->fields) : $db->quoteInto('field = ?', $this->fields[0])).' OR field = \'\')';
        }
        if($this->excludeFalsePositives){
            $sql .= ' AND falsePositive = 0';
        }
        $sql .= ' ORDER BY segmentId ASC';
        
        foreach($db->fetchAll($sql, [], Zend_Db::FETCH_ASSOC) as $row){
            $segmentId = $row['segmentId'];
            unset($row['segmentId']);
            if(!array_key_exists($segmentId, $this->data)){
                $this->data[$segmentId] = [];
            }
            if($this->addTranslations){
                $row['text'] =
                    $this->manager->translateQualityType($row['type'])
                    .$this->translationSeperator
                    .$this->manager->translateQualityCategory($row['type'], $row['category'], $this->task);
            }
            $this->data[$segmentId][] = $this->transformRow($row);
        }
    }
    /**
     * Retrieves the data for all Segments
     * @return array
     */
    public function getData() : array {
        return $this->data;
    }
    /**
     * Retrieves the aggregated data for a single segment
     * @param int $segmentId
     * @param array $default
     * @return array
     */
    public function get(int $segmentId, $default=[]) : array {
        if(array_key_exists($segmentId, $this->data)){
            return $this->transformData($this->data[$segmentId]);
        }
        return $default;
    }
}
