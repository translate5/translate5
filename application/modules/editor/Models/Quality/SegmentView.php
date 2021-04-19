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
 */
class editor_Models_Quality_SegmentView extends editor_Models_Quality_AbstractView {
    
    /**
     * Creates an entry for the frontends segment-quality model (Editor.model.quality.Segment)
     * @param editor_Models_Db_SegmentQualityRow $qualityRow
     * @param editor_Segment_Quality_Manager $manager
     * @param editor_Models_Task $task
     * @return stdClass
     */
    public static function createResultRow(editor_Models_Db_SegmentQualityRow $qualityRow, editor_Segment_Quality_Manager $manager, editor_Models_Task $task) : stdClass {
        $row = new stdClass();
        $row->id = $qualityRow->id;
        $row->segmentId = $qualityRow->segmentId;
        $row->field = $qualityRow->field;
        $row->type = $qualityRow->type;
        $row->category = $qualityRow->category;
        $row->categoryIndex = $qualityRow->categoryIndex;
        $row->falsePositive = $qualityRow->falsePositive;
        $row->typeText = $manager->translateQualityType($qualityRow->type);
        $row->text = $manager->translateQualityCategory($qualityRow->type, $qualityRow->category, $task);
        $row->filterable = $manager->isFilterableType($qualityRow->type);
        $row->falsifiable = $manager->canBeFalsePositiveCategory($qualityRow->type, $qualityRow->category);
        $provider = $manager->getProvider($qualityRow->type);
        // add props to identify the tags in the editor
        if($provider == NULL || !$provider->hasSegmentTags()){
            $row->hasTag = false;
            $row->tagName = '';
            $row->cssClass = '';
        } else {
            $row->hasTag = true;
            $row->tagName = $provider->getTagNodeName();
            $row->cssClass = $provider->getTagIndentificationClass();
        }
        return $row;
    }
    /**
     * Sorting function, sorts by typeText and text
     * @param stdClass $a
     * @param stdClass $b
     * @return number
     */
    public static function compareByTypeTitle(stdClass $a, stdClass $b){
        if($a->typeText == $b->typeText){
            return strnatcasecmp($a->text, $b->text);
        }
        return strnatcasecmp($a->typeText, $b->typeText);
    }
    
    /**
     * Overrides to only supply the qualities for a single segment
     * {@inheritDoc}
     * @see editor_Models_Quality_AbstractView::create()
     */
    protected function create(){
        foreach($this->dbRows as $dbRow){
            /* @var $dbRow editor_Models_Db_SegmentQualityRow */
            $row = self::createResultRow($dbRow, $this->manager, $this->task);
            $this->rows[] = $row;
        }
        usort($this->rows, 'editor_Models_Quality_SegmentView::compareByTypeTitle');
    }
}
