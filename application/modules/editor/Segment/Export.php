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
 * Processes a single segment field for export
 * Removes all internal tags albeit those needed for further processing (internal & mqm tags)
 * Repairs Segments with tag-faults that have been detected by the AutoQA
 */
class editor_Segment_Export {
    
    /**
     * Processes a segment for export (fix internal tag faults, remove internal tags)
     * @param editor_Segment_FieldTags $fieldTags
     * @param bool $fixFaultyTags: if set, faulty tags are automatically fixed
     * @param bool $findFaultyTags: if set, tag-faults are searched on th fly instead of relying on auto-QA data
     * @return editor_Segment_Export
     */
    public static function create(editor_Segment_FieldTags $fieldTags, bool $fixFaultyTags=true, bool $findFaultyTags=false) : editor_Segment_Export {
        return new editor_Segment_Export($fieldTags, $fixFaultyTags, $findFaultyTags);
    }
    /**
     * @var editor_Segment_FieldTags
     */
    private editor_Segment_FieldTags $fieldTags;
    /**
     * @var boolean
     */
    private bool $fixFaulty;
    /**
     * @var boolean
     */
    private bool $isFaultyInTask;
    /**
     * @var boolean
     */
    private bool $tagErrorsFixed;
    
    private function __construct(editor_Segment_FieldTags $fieldTags, bool $fixFaultyTags, bool $findFaultyTags){
        $this->fieldTags = $fieldTags;
        $this->fixFaulty = $fixFaultyTags;
        // we either find fauly tags dynamically or rely on the existing auto-QA data
        if($findFaultyTags){
            $comparison = new editor_Segment_Internal_TagComparision($fieldTags, null);
            $this->isFaultyInTask = $comparison->hasFaults();
        } else {
            $this->isFaultyInTask = in_array($fieldTags->getSegmentId(), $fieldTags->getTask()->getFaultySegmentIds());
        }
        $this->tagErrorsFixed = false;
    }
    /**
     * Processes the export
     * @return string
     */
    public function process() : string {
        // this removes all segment tags not needed for export
        $this->fieldTags = ($this->fieldTags->hasTrackChanges()) ?
            $this->fieldTags->cloneWithoutTrackChanges(editor_Segment_Quality_Manager::instance()->getAllExportedTypes()) : 
            $this->fieldTags->cloneFiltered(editor_Segment_Quality_Manager::instance()->getAllExportedTypes());
        if($this->isFaultyInTask && $this->fixFaulty){
            $repair = new editor_Segment_Internal_TagRepair($this->fieldTags, NULL);
            $this->tagErrorsFixed = $repair->hadErrors();
        }
        return $this->fieldTags->render();
    }

    /**
     * Retrieves if tag errors have been found
     * @return bool
     */
    public function hasFaultyTags(): bool {
        return $this->isFaultyInTask;
    }

    /**
     * Retrieves if tag errors have been fixed
     * @return bool
     */
    public function hasFixedFaultyTags(): bool {
        return $this->tagErrorsFixed;
    }
}
