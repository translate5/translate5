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
 * Import the whole task from an earlier exported Excel-file
 */
class editor_Models_Import_Excel {
    /**
     * @var editor_Models_ExcelExImport
     */
    protected static $excel;
    
    public static function run(editor_Models_Task $task) : void {
        // load the excel
        $tempExcelExImport = ZfExtended_Factory::get('editor_Models_ExcelExImport');
        /* @var $tempExcelExImport editor_Models_ExcelExImport */
        self::$excel = $tempExcelExImport::loadFromExcel(APPLICATION_PATH.'/Test.xlsx');
        
        // load the model that handles the t5 segments
        $t5Segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $t5Segment editor_Models_Segment */
        
        if (!self::formalCheck($task)) {
            // @TODO: where/how store errors/exceptions !?!
            error_log(__FILE__.'::'.__LINE__.'; '.__CLASS__.' -> '.__FUNCTION__.'; Excel reimport is not possible because of formal errors');
            return;
        }
        
        // load segment tagger to extract pure text from t5Segment
        $segmentTagger = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
        /* @var $segmentTagger editor_Models_Segment_InternalTag */
        
        // load diffTagger (CSV-Diff-Tagger as sample)
        $diffTagger = ZfExtended_Factory::get('editor_Models_Export_DiffTagger_Csv');
        /* @var $diffTagger editor_Models_Export_DiffTagger_Csv */
        
        foreach (self::$excel->getSegments() as $segment) {
            $tempReplacement = [];
            $t5Segment->loadBySegmentNrInTask($segment->nr, $task->getTaskGuid());
            $orgSegmentTarget = $segmentTagger->toXliff($t5Segment->getTargetEdit(), NULL, $tempReplacement);
            $newSegmentTarget = $segment->target;
            
            // restore org tags
            $newSegmentTarget = $segmentTagger->reapply2dMap($newSegmentTarget, $tempReplacement);
            
            // @TODO: add TermTagger Informations !?!
            
            // finally add TrackChanges
            $newSegmentTarget = $diffTagger->diffSegment($orgSegmentTarget, $newSegmentTarget, NULL, NULL);
            
            error_log(__FILE__.'::'.__LINE__.'; '.__CLASS__.' -> '.__FUNCTION__."\n"
                    .'alt (org):'.$t5Segment->getTargetEdit()
                    ."\nneu:".$newSegmentTarget
                    //."\nTrackChanges: ".print_r($diffTagger->diffSegment($orgSegmentTarget, $newSegmentTarget, NULL, NULL), TRUE)
                    //."\ntagDiffs: ".print_r($segmentTagger->diff($orgSegmentTarget, $newSegmentTarget), TRUE)
                    );
            continue;
            // @TODO: Terminology markup is readded by sending the segment again to the termTagger.
            // ?? is it always neded???
            
            
            // save edited segment target
            $t5Segment->setTargetEdit(date('Y-m-d H:i:s').' reimported :: '.$newSegmentTarget);
            $t5Segment->save();
            
            // save (new) comment for the segment (if not empty in excel)
            if (!empty($segment->comment)) {
                self::addComment($segment->comment, $t5Segment->getId(), $task);
            }
        }
        
        return;
    }
    
    /**
     * Add a comment to a segment.
     * @param string $comment
     * @param int $segmentId
     * @param editor_Models_Task $task
     */
    protected static function addComment(string $commentText, int $segmentId, editor_Models_Task$task) : void {
        try {
            $comment = ZfExtended_Factory::get('editor_Models_Comment');
            /* @var $comment editor_Models_Comment */
            $now = date('Y-m-d H:i:s');
            $comment->init();
            
            $comment->setModified($now);
            $comment->setCreated($now);
            
            $comment->setTaskGuid($task->getTaskGuid());
            $comment->setSegmentId($segmentId);
            
            $comment->setUserGuid($task->getPmGuid());
            $comment->setUserName($task->getPmName());
            
            $comment->setComment('Comment from external editing in Excel:'."\n".$commentText);
            
            $comment->validate();
            $comment->save();
        }
        catch (ZfExtended_UnprocessableEntity | Zend_Db_Statement_Exception $e) {
            // @TODO what to do if en error occures on validating/saving the comment
        }
    }
    
    /**
     * Do some formal checks, by comparing the informations in the excel with the informations of the task
     * - compare the task-guid
     * - compare the number of segments
     * - compare all segments if a not-empty segment in task is empty in excel
     * 
     * @param editor_Models_Task $task
     * @return bool
     */
    protected static function formalCheck(editor_Models_Task $task) : bool {
        // compare task-guid
        if ($task->getTaskGuid() != self::$excel->getTaskGuid()) {
            // @TODO: where/how store errors/exceptions !?!
            error_log(__FILE__.'::'.__LINE__.'; '.__CLASS__.' -> '.__FUNCTION__.'; task-guid differs in task compared to the excel; Task: '.$task->getTaskGuid().' vs. Excel: '.self::$excel->getTaskGuid());
            return FALSE;
        }
        
        // compare number of segments.
        $t5Segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        $tempCountTaskSegments = $t5Segment->count($task->getTaskGuid());
        
        $tempExcelSegments = self::$excel->getSegments();
        $tempCountExcelSegments = count($tempExcelSegments);
        
        if ($tempCountTaskSegments != $tempCountTaskSegments) {
            // @TODO: where/how store errors/exceptions !?!
            error_log(__FILE__.'::'.__LINE__.'; '.__CLASS__.' -> '.__FUNCTION__.'; number of segments differ in task compared to the excel; Task: '.$tempCountTaskSegments.' vs. Excel: '.$tempCountExcelSegments);
            return FALSE;
        }
        
        // compare all segments if an empty segment in excel is not-empty in task
        foreach ($tempExcelSegments as $segment) {
            if (empty($segment->target)) {
                $t5Segment->loadBySegmentNrInTask($segment->nr, $task->getTaskGuid());
            }
            
        }
        
        return TRUE;
    }
}