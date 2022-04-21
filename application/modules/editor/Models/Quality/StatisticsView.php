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
 * Creates the Data for the Qualities QA Statistics REST Endpoint
 */
class editor_Models_Quality_StatisticsView {
    
    /**
     *
     * @param stdClass $a
     * @param stdClass $b
     * @return number
     */
    public static function compareByText(stdClass $a, stdClass $b){
        return strnatcasecmp($a->text, $b->text);
    }
    
    /**
     * @var editor_Models_Task
     */
    private $task;
    /**
     * @var editor_Models_Db_SegmentQuality
     */
    private $table;
    /**
     * @var ZfExtended_Zendoverwrites_Translate
     */
    private $translate;
    /**
     * @var string
     */
    private $field;
    /**
     * @var array
     */
    private $tree;
    /**
     * @var editor_Segment_Quality_Manager
     */
    private $manager;
    /**
     * @var boolean
     */
    private $onlyMqm = false;
    /**
     * 
     * @param editor_Models_Task $task
     * @param string $field
     */
    public function __construct(editor_Models_Task $task, string $field=NULL){
        
        $this->task = $task;
        $this->table = new editor_Models_Db_SegmentQuality();
        $this->translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        $this->manager = editor_Segment_Quality_Manager::instance();
        $this->field = $this->validateField($field);
        $this->create();
    }
    /**
     * returns the desired field to get the statistics for (source or target),
     * given by user through parameter "type"
     * if nothing is given or value is invalid returns "target"
     * @param string $field
     * @return string
     */
    private function validateField(string $field=NULL) : string {
        $sfm = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
        /* @var $sfm editor_Models_SegmentFieldManager */
        $sfm->initFields($this->task->getTaskGuid());
        if($field == NULL || $sfm->getByName($field) === false){
            return $sfm->getFirstTargetName();
        }
        return $field;
    }
    /**
     * 
     * @return string
     */
    public function getDownloadName() : string {
        if($this->field == NULL){
            return $this->task->getTasknameForDownload('.xml');
        }
        return $this->task->getTasknameForDownload('-'.$this->field.'.xml');
    }
    /**
     * 
     * @return array
     */
    public function getTree() : array {
        return $this->tree;
    }
    /**
     * Creates the nested qualities Tree
     */
    private function create(){
        $this->createMqmTree();
        if(!$this->onlyMqm){
            // we use the filter-panel-views intermediate model to add the other types
            $panelView = new editor_Models_Quality_FilterPanelView($this->task, false, NULL, true, $this->field);
            foreach($panelView->getRowsByType() as $type => $typeRows){
                $row = $typeRows[editor_Models_Quality_FilterPanelView::RUBRIC];
                if($row->qcount > 0){
                    $typeNode = new stdClass();
                    $typeNode->text = $row->text;
                    $typeNode->totalTotal = $row->qcount;
                    $typeNode->total = 0;
                    $typeNode->expanded = true;                
                    $typeNode->categoryIndex = -1;
                    $typeNode->children = [];
                    foreach($typeRows as $category => $row){
                        if($category != editor_Models_Quality_FilterPanelView::RUBRIC){
                            $catNode = new stdClass();
                            $catNode->text = $row->text;
                            $catNode->totalTotal = $typeNode->totalTotal;
                            $catNode->total = $row->qcount;
                            $catNode->expanded = true;
                            $catNode->leaf = true;
                            $catNode->categoryIndex = -1;
                            $typeNode->children[] = $catNode;
                        }
                    }
                    $typeNode->leaf = (count($typeNode->children) == 0);
                    $this->tree[] = $typeNode;
                }
            }
            usort($this->tree, 'editor_Models_Quality_StatisticsView::compareByText');
        }
    }
    /**
     * Creates the nested MQM structure based on the OLD QMsubsegment Code in former times
     */
    private function createMqmTree() {
        $issue = new stdClass();
        $issue->children = $this->task->getMqmTypesTranslated(false);
        $issue->text = ($this->onlyMqm) ? $this->translate->_('Alle Kategorien') : $this->manager->translateQualityType(editor_Segment_Tag::TYPE_MQM);
        $issue->id = -1;
        $issues = [ $issue ];
                
        $storage =  new stdClass();
        $storage->severitySumKeys = array();
        $storage->statData = $this->fetchStatisticsData($this->field);
        
        $hasChildren = function($checkChilds){
            return isset($checkChilds->children) && is_array($checkChilds->children);
        };
        $walk = function($storage, $issues) use (&$walk, $hasChildren){
            foreach ($issues as $keyIssue => &$issue) {
                $hasChilds = $hasChildren($issue);
                settype($issue->totalTotal, 'integer');
                if((isset($storage->statData[$issue->id]) || $hasChilds)){
                    $issue->expanded = true;
                    $issue->leaf = !$hasChilds;
                    if(isset($storage->statData[$issue->id])){
                        foreach ($storage->statData[$issue->id] as $k => $v) {
                            if($k != 'categoryIndex' && $k != 'sum'){
                                $k = strtolower($k);
                                $issue->{$k} = (int)$v;
                                $severityKey = 'total'.ucfirst($k);
                                $storage->severitySumKeys[$severityKey] = '';
                                settype($issue->{$severityKey}, 'integer');
                                $issue->{$severityKey} += (int)$v;
                            }
                        }
                        $issue->total = $storage->statData[$issue->id]['sum'];
                        $issue->totalTotal += $storage->statData[$issue->id]['sum'];
                    }
                }
                $issue->categoryIndex = $issue->id;
                unset($issue->id);
                
                if($hasChilds){
                    $storage = $walk($storage, $issue->children, $hasChildren);
                    foreach($storage->issues as $k => $childsIssue){
                        foreach($storage->severitySumKeys as $severityKey => $v){
                            if(isset($childsIssue->{$severityKey})){
                                settype($issue->{$severityKey}, 'integer');
                                $issue->{$severityKey} += $childsIssue->{$severityKey};
                            }
                        }
                        $issue->totalTotal += $childsIssue->totalTotal;
                    }
                    $issue->children = $storage->issues;
                    $issue->children = array_values($issue->children);//ensure that we have a numerical array for json-conversion (otherwhise we will not get a json-array, but a json-object)
                }
                if($issue->totalTotal == 0){
                    unset($issues[$keyIssue]);
                }
            }
            $storage->issues = $issues;
            return $storage;
        };
        $storage = $walk($storage, $issues, $hasChildren);
        $this->tree = $storage->issues;
        
    }

    /**
     * @param string|null $field
     * @return array
     */
    private function fetchStatisticsData(string $field=NULL) : array {
        $select = $this->table->getAdapter()->select()
        ->from(
            array('q' => $this->table->getName()),
            array('categoryIndex', 'severity', 'count' => 'COUNT(*)'))
            ->group('categoryIndex')
            ->group('severity')
            ->where('taskGuid = ?', $this->task->getTaskGuid())
            ->where('type = ?', editor_Segment_Tag::TYPE_MQM);
        if(!empty($field)){
            $select->where('field = ?', $field);
        }
        $data = $this->table->getAdapter()->fetchAll($select);
        $groupedData = [];
        foreach ($data as $d) {
            $groupedData[$d['categoryIndex']]['categoryIndex'] = $d['categoryIndex'];
            $groupedData[$d['categoryIndex']][$d['severity']] = $d['count'];
        }
        foreach ($groupedData as &$categoryIndex) {
            $sum = 0;
            foreach ($categoryIndex as $key => $value) {
                if ($key !== 'categoryIndex') {
                    $sum += $value;
                }
            }
            $categoryIndex['sum'] = $sum;
        }
        return $groupedData;
    }
}
