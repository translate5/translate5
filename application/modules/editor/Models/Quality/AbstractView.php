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
     * Only for frontend development: If true, the incompleteness & faultyness of quality types will be added to be able to test the frontends more easy
     * @var boolean
     */    
    const EMULATE_PROBLEMS = false;
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
     * @var stdClass[]
     */
    protected $rows = [];
    /**
     * @var stdClass[]
     */
    protected $rowsByType = [];
    /**
     * @var stdClass[]
     */
    protected $rowsByMqmCat;
    /**
     * @var integer
     */
    protected $numQualities = 0;
    /**
     * Configures the generated data
     * @var boolean
     */
    protected $isTree = false;
    /**
     * If set (only for Trees) all nodes will have a "checked" property
     * @var boolean
     */
    protected $hasCheckboxes = false;
    /**
     * Configures the generated data (currently unused)
     * @var boolean
     */
    protected $hasNumFalsePositives = false;
    /**
     * Configures if we show all categories if they're empty or not
     * @var boolean
     */
    protected $hasEmptyCategories = false;
    /**
     * If set, all categories will be collapsed
     * @var boolean
     */    
    protected $allCategoriesCollapsed = false;
    /**
     * If set, the mqm are shown flat, otherwise as a deeper nested tree
     * @var boolean
     */
    protected $mqmIsFlat = false;
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
    protected $checkedQualities = NULL;
    /**
     * May be set by request and holds the list of collapsed tree nodes
     * @var array
     */
    protected $collapsedQualities = NULL;
    /**
     * The current restriction for the falsePositive column. NULL means no restriction
     * @var int
     */
    protected $falsePositiveRestriction = NULL;
    /**
     * The restriction for the current user
     * @var string
     */
    protected $segmentNrRestriction = NULL;
    /**
     * 
     * @param editor_Models_Task $task
     * @param int $segmentId
     * @param bool $onlyFilterTypes
     * @param string $currentState: The format of the value equals that of the filter-value $type:$category for the qualities-grid-filter but may has additional entries for types only
     * @param bool $excludeMQM: only needed for Statistics view
     * @param string $field: optional to limit the fetched qualities to a certain field
     */
    public function __construct(editor_Models_Task $task, int $segmentId=NULL, bool $onlyFilterTypes=false, string $currentState=NULL, bool $excludeMQM=false, string $field=NULL){
        $this->task = $task;
        $this->taskConfig = $this->task->getConfig();
        $this->manager = editor_Segment_Quality_Manager::instance();
        $this->translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        $this->excludeMQM = $excludeMQM;
        $this->table = new editor_Models_Db_SegmentQuality();
        // generate hashtable of filtered qualities and respect filter mode if the current state was sent
        if($currentState !== NULL){
            $requestState = new editor_Models_Quality_RequestState($currentState);
            $this->checkedQualities = $requestState->getCheckedList();
            $this->collapsedQualities = $requestState->getCollapsedList();
            $this->falsePositiveRestriction = $requestState->getFalsePositiveRestriction();
            // The qualities may have to be limited to the visible segment-nrs for the current editor
            $this->segmentNrRestriction = $requestState->getUserRestrictedSegmentNrs($this->task);
            
        } else if($this->isTree){
            // In tree mode we need the user-restriction of the state also when no filtered state was send
            $requestState = new editor_Models_Quality_RequestState('');
            $this->segmentNrRestriction = $requestState->getUserRestrictedSegmentNrs($this->task);
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
        $this->dbRows = $this->table->fetchFiltered(
            $task->getTaskGuid(),
            $segmentId,
            $blacklist,
            true,
            NULL,
            $this->falsePositiveRestriction,
            $this->segmentNrRestriction,
            $field,
            ['type ASC','category ASC']);
        
        // error_log('PRESETS: checked: '.print_r($this->checkedQualities, true).' / falsePositives:'.$this->falsePositiveRestriction.' / collapsed: '.print_r($this->collapsedQualities, true).' / DBrows: '.count($this->dbRows));
     
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
        $root->qtotal = $this->numQualities;
        $root->qcategory = null;
        $root->qcomplete = true;        
        $root->expanded = true;
        $root->expandable = false; // removes the error in front of the root node
        if($this->hasCheckboxes){
            $root->checked = $this->getCheckedVal($root->qtype, $root->qcategory);
        }
        $root->text = $this->manager->getTranslate()->_('Alle Kategorien');
        $root->children = $this->rows;
        return [ $root ];
    }
    /**
     * Retrieves the metadata for the qualities (cleaned overall count, info about internal tag faults)
     * @return stdClass
     */
    public function getMetaData() : stdClass {
        $metadata = new stdClass();
        $metadata->numSegments = $this->numQualities;
        $metadata->hasFaultyInternalTags = $this->hasFaultyInternalTags;
        return $metadata;
    }
    /**
     * Retrieves the processed data
     * @return stdClass[]
     */
    public function getRows() : array {
        return $this->rows;
    }
    /**
     * Retrieves the intermediate internal model
     * @return stdClass[]
     */
    public function getRowsByType() : array {
        return $this->rowsByType;
    }
    /**
     * Evaluates the view rows for counted views
     */
    protected function create(){
        // create ordered rubrics
        $rubrics = [];
        foreach($this->manager->getAllFilterableTypes($this->task) as $type){
            if(!$this->excludeMQM || $type != editor_Segment_Tag::TYPE_MQM){
                $rubrics[] = $this->createRubricRow($type);
            }
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
                if($this->hasNumFalsePositives && $row->falsePositive == 1){
                    $this->rowsByType[$row->type][self::RUBRIC]->qcountfp++;
                }
                if($this->isTree){
                    if(!array_key_exists($row->category, $this->rowsByType[$row->type])){
                        $this->rowsByType[$row->type][$row->category] = $this->createCategoryRow($row, false);
                    }
                    $this->rowsByType[$row->type][$row->category]->qcount++;
                    if($this->hasNumFalsePositives && $row->falsePositive == 1){
                        $this->rowsByType[$row->type][$row->category]->qcountfp++;
                    }
                }
                $this->numQualities++;
            }
            if($row->type == editor_Segment_Tag::TYPE_INTERNAL && editor_Segment_Internal_TagComparision::isFault($row->type, $row->category)){
                $this->hasFaultyInternalTags = true;
            }
        }
        // create result rows
        $this->createRows($rubrics);
    }
    /**
     * Create the resulting view out of the database data
     * @param stdClass[] $rubrics
     */
    protected function createRows(array $rubrics){
        foreach($rubrics as $rubric){
            if($this->isTree){
                $rubric->qtotal = $rubric->qcount;
                if($rubric->qtype == editor_Segment_Tag::TYPE_MQM){
                    // create mqm-subtree if we have mqms
                    if($rubric->qcount > 0 || $this->hasEmptyCategories){
                        $this->addMqmRows($rubric, $this->rowsByType[$rubric->qtype]);
                    }
                } else {
                    $rubric->children = [];
                    $qualityProvider = $this->manager->getProvider($rubric->qtype);
                    $rubricCats = ($this->hasEmptyCategories) ? $qualityProvider->getAllCategories($this->task) : array_keys($this->rowsByType[$rubric->qtype]);
                    ksort($rubricCats);
                    foreach($rubricCats as $category){
                        if($category != self::RUBRIC){
                            if(array_key_exists($category, $this->rowsByType[$rubric->qtype])){
                                $rubric->children[] = $this->rowsByType[$rubric->qtype][$category];
                            } else {
                                $row = $this->createNonDbRow($qualityProvider->translateCategory($this->translate, $category, $this->task), $rubric->qtype, $category);
                                $rubric->children[] = $row;
                            }
                        }
                    }
                } 
                $this->finalizeTree($rubric);
            } else {
                if($rubric->qtype == editor_Segment_Tag::TYPE_INTERNAL){
                    if(self::EMULATE_PROBLEMS){
                        $rubric->qcomplete = false;
                        $rubric->qfaulty = true;
                    } else if($this->hasFaultyInternalTags){
                        $rubric->qfaulty = true;
                    }
                }
            }
            $this->rows[] = $rubric;
        }
    }
    /**
     * Adds the props that ExtJs needs to build a proper tree & repairs some of the quirks
     * @param stdClass $row
     * @param boolean $isRubric
     */
    protected function finalizeTree(stdClass $row, $isRubric=true){
        if(property_exists($row, 'children') && count($row->children) > 0){
            // can be set in various ways
            $row->expanded = $this->getExpandedVal($row);
            // recursive processing
            foreach($row->children as $child){
                $this->finalizeTree($child, false);
            }
        } else {
            if(property_exists($row, 'children')){
                unset($row->children);
            }
            $row->leaf = true;
        }
        if($isRubric && !property_exists($row, 'qtotal')){
            $row->qtotal = $row->qcount;
        }
        if($this->hasCheckboxes){
            if($isRubric){
                // rubrics without segments shall not be checked
                $row->checked = ($row->qtotal > 0) ? $this->getCheckedVal($row->qtype, $row->qcategory) : false;
            } else {
                $row->checked = $this->getCheckedVal($row->qtype, $row->qcategory);
            }
        }
        // mark the faulty item
        if($row->qtype == editor_Segment_Tag::TYPE_INTERNAL && $row->qcategory == editor_Segment_Internal_TagComparision::TAG_STRUCTURE_FAULTY){
            $row->qfaulty = true;
        }
        // To easily test the incomplete & faulty configs in the frontend
        if(self::EMULATE_PROBLEMS){
            if($row->qtype == editor_Segment_Tag::TYPE_INTERNAL){
                if($row->qcategory == NULL){
                    $row->qcomplete = false;
                } else {
                    $row->qfaulty = true;
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
        $row->qid = -1;
        $row->qtype = $type;
        $row->qcount = 0;
        // TODO AUTOQA: When the re-check/re-analysis is implemented the buttons need to be activated here
        // $row->qcomplete = $this->manager->isFullyCheckedType($type, $this->taskConfig);
        $row->qcomplete = true;
        if($this->isTree){
            $row->children = [];
            $row->qcategory = NULL;
        }
        if($this->hasNumFalsePositives){
            $row->qcountfp = 0;
        }
        $row->text = $this->manager->translateQualityType($type);
        return $row;
    }
    /**
     * Creates a category rowbased based on a real DB-entry
     * @param editor_Models_Db_SegmentQualityRow $dbRow
     * @return stdClass
     */
    protected function createCategoryRow(editor_Models_Db_SegmentQualityRow $dbRow) : stdClass {
        $row = new stdClass();
        $row->qid = $dbRow->id;
        $row->qtype = $dbRow->type;
        $row->qcount = 0;
        $row->qcomplete = true;
        if($this->isTree){
            $row->children = [];
            $row->qcategory = $dbRow->category;
            $row->qcatidx = $dbRow->categoryIndex;
        }
        if($this->hasNumFalsePositives){
            $row->qcountfp = 0;
        }
        $row->text = $this->manager->translateQualityCategory($dbRow->type, $dbRow->category, $this->task);
        return $row;
    }
    /**
     * Creates a non-DB non-rubric row
     * @param string $text
     * @param string $type
     * @param string $category
     * @return stdClass
     */
    protected function createNonDbRow(string $text, string $type, string $category=NULL) : stdClass {
        $row = new stdClass();
        $row->qid = -1;
        $row->qtype = $type;
        $row->qcount = 0;
        $row->qcomplete = true;
        if($this->isTree){
            $row->children = [];
            $row->qcategory = $category;
        }
        if($this->hasNumFalsePositives){
            $row->qcountfp = 0;
        }
        $row->text = $text;
        return $row;
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
    /**
     * Evaluates the expanded state of a rubric/category
     * @param stdClass $row
     * @return bool
     */
    protected function getExpandedVal(stdClass $row) : bool {
        // all cats collapsed: easy
        if($this->allCategoriesCollapsed){
            return false;
        }
        // a collapsed state was sent by request
        if($this->collapsedQualities != null){
            $typeCat = ($row->qcategory === null || $row->qcategory === '') ? $row->qtype : $row->qtype.':'.$row->qcategory;
            return (array_key_exists($typeCat, $this->collapsedQualities) == false);
        }
        // empty rubrics/categories will not be expanded by default
        if($this->hasEmptyCategories){
            return ((property_exists($row, 'qtotal') && $row->qtotal > 0));
        }
        // the "absolute default" is to be expanded
        return true;
    }
    /**
     * Generates the MQM children, which usually are deeper nested
     * @param stdClass $rubric
     * @param stdClass[] $rowsByMqmCat
     */
    protected function addMqmRows(stdClass $rubric, array $rowsByMqmCat){
        $this->rowsByMqmCat = $rowsByMqmCat;
        // turn the mqm-tree to our model
        $mqmNodes = $this->task->getMqmTypesTranslated(false);
        if($this->mqmIsFlat){
            $this->addMqmRowsFromNode($rubric, $mqmNodes);
        } else {
            foreach($mqmNodes as $mqmNode){
                $child = $this->createMqmRowFromNode($mqmNode);
                // only add mqm-children that have segments
                if($child->qtotal > 0 || $this->hasEmptyCategories){
                    $rubric->children[] = $child;
                }
            }
        }
    }
    /**
     * Recursive function to add a tree of mqm types as tree to our model
     * @param stdClass $mqmType: a stdClass with the props id, text & children where children is optional
     */
    protected function createMqmRowFromNode(stdClass $mqmNode){
        $category = editor_Segment_Mqm_Tag::createCategoryVal($mqmNode->id);
        $hasChildren = (isset($mqmNode->children) && is_array($mqmNode->children) && count($mqmNode->children) > 0);
        if(array_key_exists($category, $this->rowsByMqmCat)){
            $row = $this->rowsByMqmCat[$category];
        } else {
            $row = $this->createNonDbRow($mqmNode->text, editor_Segment_Tag::TYPE_MQM, $category);
            $row->qcatidx = $mqmNode->id;
        }
        if($hasChildren){
            $addTotal = 0;
            foreach($mqmNode->children as $mqmChild){
                $child = $this->createMqmRowFromNode($mqmChild);
                if($child->qtotal > 0 || $this->hasEmptyCategories){
                    $addTotal += $child->qtotal;
                    $row->children[] = $child;
                }
            }
            $row->qtotal = $row->qcount + $addTotal;
        } else {
            $row->qtotal = $row->qcount;
        }
        return $row;
    }
    /**
     * Recursive function to add a tree of mqm types as flat children to a rubric
     * @param stdClass $rubric
     * @param array $mqmNodes
     */
    protected function addMqmRowsFromNode(stdClass $rubric, array $mqmNodes){
        foreach($mqmNodes as $mqmNode){
            $category = editor_Segment_Mqm_Tag::createCategoryVal($mqmNode->id);            
            if(array_key_exists($category, $this->rowsByMqmCat)){
                $row = $this->rowsByMqmCat[$category];
                $rubric->children[] = $row;
            } else if($this->hasEmptyCategories) {
                $row = $this->createNonDbRow($mqmNode->text, editor_Segment_Tag::TYPE_MQM, $category);
                $row->qcatidx = $mqmNode->id;
                $rubric->children[] = $row;
            }
            if(isset($mqmNode->children) && is_array($mqmNode->children) && count($mqmNode->children) > 0){
                $this->addMqmRowsFromNode($rubric, $mqmNode->children);
            }
        }
    }
}
