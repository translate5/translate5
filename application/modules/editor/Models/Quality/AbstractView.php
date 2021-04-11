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
     * The initial value for the tree-nodes checked prop
     * @var boolean
     */
    const CHECKED_DEFAULT = false;
    /**
     * the initially shown filter
     * @var string
     */
    const FILTER_MODE_DEFAULT = 'all';
    /**
     * 
     * @param stdClass $a
     * @param stdClass $b
     * @return number
     */
    public static function compareByTitle(stdClass $a, stdClass $b){
        return strnatcasecmp($a->text, $b->text);
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
     * @var editor_Segment_Quality_Manager
     */
    protected $manager;
    /**
     * @var ZfExtended_Zendoverwrites_Translate
     */
    protected $translate;
    /**
     * @var editor_Models_Db_SegmentQuality
     */
    protected $table;
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
     * @var integer
     */
    protected $numQualities = 0;
    /**
     * @var boolean
     */
    protected $isTree = false;
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
     * May be set by request and holds the list of checked tree nodes
     * @var array
     */
    protected $checkedQualities = null;
    /**
     * The current restriction for the falsePositive column. NULL means no restriction
     * @var int
     */
    protected $falsePositiveRestriction = NULL;
    /**
     * 
     * @param editor_Models_Task $task
     * @param int $segmentId
     * @param bool $onlyFilterTypes
     * @param string $currentState: The format of the value equals that of the filter-value $type:$category for the qualities-grid-filter but may has additional entries for types only
     * @param bool $excludeMQM: only needed for Statistics view
     * @param string $field: only needed for Statistics view
     */
    public function __construct(editor_Models_Task $task, int $segmentId=NULL, bool $onlyFilterTypes=false, string $currentState=NULL, bool $excludeMQM=false, $field=NULL){
        $this->task = $task;
        $this->taskConfig = $this->task->getConfig();
        $this->manager = editor_Segment_Quality_Manager::instance();
        $this->excludeMQM = $excludeMQM;
        $this->table = new editor_Models_Db_SegmentQuality();
        // generate hashtable of filtered qualities and respect filter mode if the current state was sent
        if($currentState !== NULL){
            $requestState = new editor_Models_Quality_RequestState($currentState);
            $this->checkedQualities = $requestState->getCheckedList();
            $this->falsePositiveRestriction = $requestState->getFalsePositiveRestriction();
        }
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
        $this->dbRows = $this->table->fetchFiltered($task->getTaskGuid(), $segmentId, $field, $blacklist, true, NULL, $this->falsePositiveRestriction, ['type ASC','category ASC']);
        
        // TODO AUTOQA: remove
        // error_log('PRESETS: '.print_r($this->checkedQualities, true).' / falsePositives:'.$this->falsePositiveRestriction.' / DBrows: '.count($this->dbRows));
        
        $this->create();
    }
    /**
     * Retrieves the root node for the quality filter store wrapped in an array
     * @return array
     */
    public function getTree(){
        $root = new stdClass();
        $root->qid = -1;
        $root->qtype = 'root'; // QUIRK: must not become a real quality ... highly unlikely, as we define the quality-types by code :-)
        $root->qcount = $this->numQualities;
        $root->qcategory = null;
        $root->qcomplete = true;
        $root->qroot = true;
        $root->expanded = true;
        $root->expandable = false;
        $root->checked = $this->getCheckedVal($root->qtype, $root->qcategory);
        $root->text = $this->manager->getTranslate()->_('Alle Kategorien');
        $root->segmentIds = [];
        $root->children = $this->rows;
        return [ $root ];
    }
    /**
     * Retrieves the metadata for the qualities (cleaned overall count, info about internal tag faults)
     * @return stdClass
     */
    public function getMetaData(){
        $metadata = new stdClass();
        $metadata->numQualities = $this->numQualities;
        $metadata->hasFaultyInternalTags = $this->hasFaultyInternalTags;
        return $metadata;
    }
    /**
     * Retrieves the processed data
     * @return array
     */
    public function getRows(){
        return $this->rows;
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
        $qmRows = $this->fetchQmRows();
        $qmType = editor_Segment_Tag::TYPE_QM;
        foreach($this->manager->getAllTypes() as $type){
            if(!$this->excludeMQM || $type != editor_Segment_Tag::TYPE_MQM){
                $rubrics[] = $this->createRubricRow($type);
            }
        }
        // since the QM type is not part of the LEK_segment_quality model we have to add it seperately
        if(count($qmRows) > 0){
            $this->translate = ZfExtended_Zendoverwrites_Translate::getInstance();
            $rubrics[] = $this->createQmRow($this->translate->_(strtoupper($qmType)), '', true);
        }
        usort($rubrics, 'editor_Models_Quality_AbstractView::compareByTitle');
        // create intermediate model
        foreach($rubrics as $rubric){
            $this->rowsByType[$rubric->qtype] = [];
            $this->rowsByType[$rubric->qtype][self::RUBRIC] = $rubric;
        }
        // add categories to intermediate model
        foreach($this->dbRows as $row){
            /* @var $row editor_Models_Db_SegmentQualityRow */
            if(array_key_exists($row->type, $this->rowsByType)){
                $this->rowsByType[$row->type][self::RUBRIC]->qcount++;
                if($this->isTree){
                    if(!array_key_exists($row->category, $this->rowsByType[$row->type])){
                        $this->rowsByType[$row->type][$row->category] = $this->createCategoryRow($row, false);
                    }
                    $this->rowsByType[$row->type][$row->category]->qcount++;
                    if($this->hasSegmentIds){
                        $this->rowsByType[$row->type][$row->category]->segmentIds[] = $row->segmentId;
                    }
                }
                $this->numQualities++;
            }
        }
        // add QM entries to our model if there are any
        if(count($qmRows) > 0){
            $utility = ZfExtended_Factory::get('editor_Models_Segment_Utility');
            /* @var $utility editor_Models_Segment_Utility */
            // add QMs from the segments model
            foreach($this->fetchQmRows() as $row){
                $segmentId = $row['id'];
                foreach($utility->convertQmIds($row['qmId']) as $index => $name){
                    $category = $qmType.'_'.$index; // analogue to what is made with mqm-categories
                    $this->rowsByType[$qmType][self::RUBRIC]->qcount++;
                    if($this->hasSegmentIds){
                        $this->rowsByType[$qmType][self::RUBRIC]->segmentIds[] = $segmentId;
                    }
                    if($this->isTree){
                        if(!array_key_exists($category, $this->rowsByType[$qmType])){
                            $this->rowsByType[$qmType][$category] = $this->createQmRow($this->translate->_($name), $category, false);
                        }
                        $this->rowsByType[$qmType][$category]->qcount++;
                        if($this->hasSegmentIds){
                            $this->rowsByType[$qmType][$category]->segmentIds[] = $segmentId;
                        }
                    }
                    $this->numQualities++;
                }
            }
        }
        // check if we have a structural internal tag problem
        if(array_key_exists(editor_Segment_Tag::TYPE_INTERNAL, $this->rowsByType) && $this->rowsByType[editor_Segment_Tag::TYPE_INTERNAL][self::RUBRIC]->qcount > 0){
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
            if($this->isTree){
                $rubric->children = [];
                ksort($this->rowsByType[$rubric->qtype]);
                foreach($this->rowsByType[$rubric->qtype] as $category => $row){
                    if($category != self::RUBRIC){
                        $rubric->children[] = $row;
                    }
                }
                if(count($rubric->children) == 0){
                    $rubric->leaf = true;
                    $rubric->checked = false;
                }
            }
            $this->rows[] = $rubric;
        }
    }
    /**
     * Creates a rubric row
     * @param string $type
     * @return stdClass
     */
    protected function createRubricRow(string $type) : stdClass {
        $row = new stdClass();
        $row->qid = -1;
        $row->qtype = $type;
        $row->qcount = 0;
        $row->qcomplete = $this->manager->isFullyCheckedType($type, $this->taskConfig);
        $row->checked = $this->getCheckedVal($type);
        if($this->isTree){
            $row->qcategory = null;
            $row->qroot = false;
            $row->expanded = true;
        }
        if($this->hasSegmentIds){
            $row->segmentIds = [];
        }
        $row->text = $this->manager->translateQualityType($type);
        return $row;
    }
    /**
     * Creates a category row
     * @param editor_Models_Db_SegmentQualityRow $dbRow
     * @return stdClass
     */
    protected function createCategoryRow(editor_Models_Db_SegmentQualityRow $dbRow) : stdClass {
        $row = new stdClass();
        $row->qid = $dbRow->id;
        $row->qtype = $dbRow->type;
        $row->qcount = 0;
        $row->qcomplete = true;
        $row->checked = $this->getCheckedVal($dbRow->type, $dbRow->category);
        if($this->isTree){
            $row->qcategory = $dbRow->category;
            $row->qroot = false;
            $row->leaf = true;
        }
        if($this->hasSegmentIds){
            $row->segmentIds = [];
        }
        $row->text = $this->manager->translateQualityCategory($dbRow->type, $dbRow->category, $this->task);
        return $row;
    }
    /**
     * Creates a QM row
     * @param editor_Models_Db_SegmentQualityRow $dbRow
     * @return stdClass
     */
    protected function createQmRow(string $text, string $category, bool $isRubric) : stdClass {
        $row = new stdClass();
        $row->qid = -1;
        $row->qtype = editor_Segment_Tag::TYPE_QM;
        $row->qcount = 0;
        $row->qcomplete = true;
        $row->checked = $this->getCheckedVal(editor_Segment_Tag::TYPE_QM, $category);
        if($this->isTree){
            $row->qcategory = ($isRubric) ? null : $category;
            $row->qroot = false;
            if($isRubric){
                $row->expanded = true;
            } else {
                $row->leaf = false;
            }
        }
        if($this->hasSegmentIds){
            $row->segmentIds = [];
        }
        $row->text = $text;
        return $row;
    }
    /**
     * 
     * @return array
     */
    protected function fetchQmRows(){
        // since QMs can not be false positive we exclude them when filtering for false positives
        if($this->falsePositiveRestriction === 1){
            return [];
        }
        $select = $this->table->select()
            ->setIntegrityCheck(false)
            ->from('LEK_segments', array('id', 'qmId'))
            ->where('taskGuid = ?', $this->task->getTaskGuid())
            ->where('qmId != ?', '')
            ->where('qmId IS NOT NULL');
        return $this->table->fetchAll($select, 'id ASC')->toArray();
    }
    /**
     * Evaluates the checked-value, if request-vals have been sent by request or by default-value otherwise
     * @param string $type
     * @param string $category
     * @return bool
     */
    protected function getCheckedVal(string $type, string $category=null) : bool {
        if($this->checkedQualities == null){
            return self::CHECKED_DEFAULT;
        }
        if($category === null || $category === ''){
            return array_key_exists($type, $this->checkedQualities);
        }
        return array_key_exists($type.':'.$category, $this->checkedQualities);
    }
}
