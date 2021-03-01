<?php
/*
 START LICENSE AND COPYRIGHT
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
 
 This file is part of a plug-in for translate5.
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 For the license of this plug-in, please see below.
 
 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html
 
 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5.
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
 http://www.gnu.org/licenses/gpl.html
 http://www.translate5.net/plugin-exception.txt
 
 END LICENSE AND COPYRIGHT
 */

/**
 * 
 * Helper to process alike-segments what can not be done in the context of the ManualQuality Provider
 * TODO: if more qualities require a alike-segment processing we may better add an explicit API in the Quality Manager / Providers
 */
class editor_Segment_ManualQuality_AlikeSegmentHelper {
    
    /**
     * @var editor_Models_Task
     */
    private $task;
    /**
     * @var boolean
     */
    private $enabled;
    /**
     * @var boolean
     */
    private $changed;
    
    /**
     * @param editor_Models_Task $task
     */
    public function __construct(editor_Models_Task $task){
        $this->task = $task;
        $this->enabled = ($task->getConfig()->runtimeOptions->editor->enableQmSubSegments == 1);
    }
    /**
     * Retrieves of MQMs are generally enabled
     * @return bool
     */
    public function isEnabled() : bool {
        return $this->enabled;
    }
    /**
     * Can only be called after ::copyTags was called and retrieves, if the content of the to field has changed (any mqm tags were added / removed)
     * @return bool
     */
    public function wasChanged() : bool {
        return $this->changed;
    }
    /**
     * Copies all MQM tags from a source  string to a target string.
     * This covers writing/cleaning the quality-model entries for the tags
     * @param string $fromFieldText
     * @param int $fromSegmentId
     * @param string $toFieldText
     * @param int $toSegmentId
     * @param string $field
     * @return string
     */
    public function copyTags(string $fromFieldText, int $fromSegmentId, string $toFieldText, int $toSegmentId, string $field) : string {
        // TODO AUTOQA REMOVE
        error_log("editor_Segment_ManualQuality_AlikeSegmentHelper( $fromFieldText, $fromSegmentId, $toFieldText, $toSegmentId, $field )");
        
        $this->changed = false;
        $type = editor_Segment_Tag::TYPE_MANUALQUALITY;
        $fromTags = new editor_Segment_FieldTags($fromSegmentId, $field, $fromFieldText, $field, $field);
        $toTags = new editor_Segment_FieldTags($toSegmentId, $field, $toFieldText, $field, $field);
        $fromHasMqm = $fromTags->hasType($type);
        $toHasMqm = $toTags->hasType($type);
        // if neither source nor target has mqm tags we return the input
        if(!$fromHasMqm && !$toHasMqm){
            return $toFieldText;
        }
        $this->changed = true;
        $table = ZfExtended_Factory::get('editor_Models_Db_SegmentQuality');
        /* @var $table editor_Models_Db_SegmentQuality */
        if($toHasMqm){
            // clean existing MQMs
            $toTags->removeByType($type);
            $table->removeQualitiesBySegmentAndType($toSegmentId, $type);
        }
        if($fromHasMqm){
            $taskGuid = $this->task->getTaskGuid();
            foreach($fromTags->getByType($type) as $mqmTag){                
                /* @var $mqmTag editor_Segment_ManualQuality_Tag */
                $mqmTag = $mqmTag->clone(true);
                $qualityId = $table->saveQuality(
                    $toSegmentId,
                    $taskGuid,
                    $field,
                    $type,
                    NULL,
                    $mqmTag->getTypeIndex(),
                    $mqmTag->getSeverity(),
                    $mqmTag->getComment(),
                    $mqmTag->startIndex,
                    $mqmTag->endIndex);
                // update the sequence-id with the database-id of the bound quality
                $mqmTag->setData('seq', strval($qualityId));
                // transfer tag to target
                $toTags->addTag($mqmTag);                
            }
        }
        return $toTags->render();
    }
}
