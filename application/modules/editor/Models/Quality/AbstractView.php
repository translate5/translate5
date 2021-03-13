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
 * 
 * Base class for the view generators of the quality endpoints
 */
abstract class editor_Models_Quality_AbstractView {
    
    /**
     * Used as category name for the rubric's
     * @var string
     */
    const RUBRIC = '__RUBRIC__';
    /**
     * 
     * @param stdClass $a
     * @param stdClass $b
     * @return number
     */
    public static function compareByTitle(stdClass $a, stdClass $b){
        return strnatcasecmp($a->title, $b->title);
    }
   
    /**
     * @var editor_Models_Task
     */
    protected $task;
    /**
     * @var Zend_Config
     */
    protected $taskConfig;
    /**
     * @var Zend_Db_Table_Rowset_Abstract
     */
    protected $dbRows;
    /**
     * @var array
     */
    protected $rows = [];
    /**
     * @var array
     */
    protected $rowsByType = [];
    /**
     * @var editor_Segment_Quality_Manager
     */
    protected $manager;
    /**
     * @var boolean
     */
    protected $hasCategories = false;
    /**
     * @var boolean
     */
    protected $hasSegmentIds = false;
    /**
     * @var boolean
     */
    protected $hasFaultyInternalTags = false;
    /**
     * @var boolean
     */
    protected $excludeMQM;
    
    /**
     * 
     * @param editor_Models_Task $task
     * @param int $segmentId
     * @param bool $onlyFilterTypes
     * @param bool $excludeMQM: only needed for Statistics view
     * @param string $field: only needed for Statistics view
     */
    public function __construct(editor_Models_Task $task, int $segmentId=NULL, bool $onlyFilterTypes=false, bool $excludeMQM=false, $field=NULL){
        $this->task = $task;
        $this->taskConfig = $this->task->getConfig();
        $this->manager = editor_Segment_Quality_Manager::instance();
        $this->excludeMQM = $excludeMQM;
        $table = new editor_Models_Db_SegmentQuality();
        $blacklist = NULL;
        if($onlyFilterTypes){
            $blacklist = $this->manager->getFilterTypeBlacklist();
        }
        if($excludeMQM){
            if($blacklist == NULL){
                $blacklist = [];
            }
            if(!in_array(editor_Segment_Tag::TYPE_MQM, $blacklist)){
                $blacklist[] = editor_Segment_Tag::TYPE_MQM;
            }
        }
        // ordering is crucial !
        $this->dbRows = $table->fetchFiltered($task->getTaskGuid(), $segmentId, $field, $blacklist, true, NULL, ['type ASC','category ASC']);
        $this->create();
        
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        $translate->_('MQM');
        $translate->_('Match-Analyse');
        $translate->_('Unbearbeiteter Fuzzy');
        $translate->_('Bearbeiteter 100% Match');
        $translate->_('Terminologie');
        $translate->_('Term wurde in der Zielsprache nicht gefunden');
        $translate->_('Term ist in der Zielsprache nicht definiert');
        $translate->_('Term ist in der Zielsprache ersetzt, veraltet oder verboten');
        $translate->_('Interne Tags');        
        $translate->_('Interne Tags fehlen');        
        $translate->_('Interne Tags wurden hinzugefügt');        
        $translate->_('Die Internen Tags haben eine ungültige Struktur');
    }
    /**
     * Retrieves the processed data
     * @return array
     */
    public function getRows(){
        return $this->rows;
    }
    /**
     * Retrieves the number of found qualities
     * @return int
     */
    public function getNumQualities(){
        return count($this->dbRows);
    }
    /**
     * Retrieves if there is a structural problem with internal tags
     * @return boolean
     */
    public function hasInternalTagFaults(){
        return $this->hasFaultyInternalTags;
    }
    /**
     * Retrieves the intermediate internal model
     * @return array
     */
    public function getRowsByType(){
        return $this->rowsByType;
    }
    /**
     * Evaluates the view rows for counted views
     */
    protected function create(){
        // create ordered rubrics
        $rubrics = [];
        foreach($this->manager->getAllTypes() as $type){
            if(!$this->excludeMQM || $type != editor_Segment_Tag::TYPE_MQM){
                $rubrics[] = $this->createRubricRow($type);
            }
        }
        usort($rubrics, 'editor_Models_Quality_AbstractView::compareByTitle');
        // create intermediate model
        foreach($rubrics as $rubric){
            $this->rowsByType[$rubric->type] = [];
            $this->rowsByType[$rubric->type][self::RUBRIC] = $rubric;
        }
        // add categories to intermediate model
        foreach($this->dbRows as $row){
            /* @var $row editor_Models_Db_SegmentQualityRow */
            if(array_key_exists($row->type, $this->rowsByType)){
                $this->rowsByType[$row->type][self::RUBRIC]->count++;
                if($this->hasSegmentIds){
                    $this->rowsByType[$row->type][self::RUBRIC]->segments[] = $row->segmentId;
                }
                if($this->hasCategories){
                    if(!array_key_exists($row->category, $this->rowsByType[$row->type])){
                        $this->rowsByType[$row->type][$row->category] = $this->createCategoryRow($row, false);
                    }
                    $this->rowsByType[$row->type][$row->category]->count++;
                    if($this->hasSegmentIds){
                        $this->rowsByType[$row->type][$row->category]->segments[] = $row->segmentId;
                    }
                }
            }
        }
        // check if we have a structural internal tag problem
        if(array_key_exists(editor_Segment_Tag::TYPE_INTERNAL, $this->rowsByType) && $this->rowsByType[editor_Segment_Tag::TYPE_INTERNAL][self::RUBRIC]->count > 0){
            $this->hasFaultyInternalTags = array_key_exists(editor_Segment_Internal_TagComparision::TAG_STRUCTURE_FAULTY, $this->rowsByType[editor_Segment_Tag::TYPE_INTERNAL]);
        }
        // create result rows
        $this->createRows($rubrics);
    }
    /**
     * Hook to create the result rows
     * @param array $rubrics
     */
    protected function createRows(array $rubrics){
        foreach($rubrics as $rubric){
            $this->rows[] = $rubric;
            if($this->hasCategories){
                ksort($this->rowsByType[$rubric->type]);
                foreach($this->rowsByType[$rubric->type] as $category => $row){
                    if($category != self::RUBRIC){
                        $this->rows[] = $row;
                    }
                }
            }
        }
    }
    /**
     * Creates a rubric row
     * @param string $type
     * @return stdClass
     */
    protected function createRubricRow(string $type) : stdClass {
        $row = new stdClass();
        $row->id = -1;
        $row->type = $type;
        $row->count = 0;
        $row->checked = $this->manager->isFullyCheckedType($type, $this->taskConfig);
        if($this->hasCategories){
            $row->category = null;
            $row->rubric = true;
        }
        if($this->hasSegmentIds){
            $row->segments = [];
        }
        $row->title = $this->manager->translateQualityType($type);
        return $row;
    }
    /**
     * Creates a category row and adds it to the rows
     * @param editor_Models_Db_SegmentQualityRow $dbRow
     * @return stdClass
     */
    protected function createCategoryRow(editor_Models_Db_SegmentQualityRow $dbRow) : stdClass {
        $row = new stdClass();
        $row->id = $dbRow->id;
        $row->type = $dbRow->type;
        $row->count = 0;
        $row->checked = true; // only rubrics should show a "not checked properly" hint
        if($this->hasCategories){
            $row->category = $dbRow->category;
            $row->rubric = false;
        }
        if($this->hasSegmentIds){
            $row->segments = [];
        }
        $row->title = $this->manager->translateQualityCategory($dbRow->type, $dbRow->category, $this->task);
        return $row;
    }
}
