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
 * Export the whole task as an Excel-file
 */
class editor_Models_Export_Excel {
    /**
     * @var editor_Models_Excel_ExImport
     */
    protected static $excel;
    
    /**
     * export xls from $task.
     * @param editor_Models_Task $task
     */
    public static function run(editor_Models_Task $task) : void {
        try {
            // task data must be aktualiced
            $task->createMaterializedView();
            
            // create a new empty excel
            $tempExcelExImport = ZfExtended_Factory::get('editor_Models_Excel_ExImport');
            /* @var $tempExcelExImport editor_Models_Excel_ExImport */
            self::$excel = $tempExcelExImport::createNewExcel($task);
            
            
            // load segment tagger to extract pure text from segments
            $segmentTagger = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
            /* @var $segmentTagger editor_Models_Segment_InternalTag */
            
            // create a segment-iterator to get all segments of this task as a list of editor_Models_Segment objects
            $segments = ZfExtended_Factory::get('editor_Models_Segment_Iterator', [$task->getTaskGuid()]);
            /* @var $segments editor_Models_Segment_Iterator */
            
            // write the segments into the excel
            foreach($segments as $segment) {
                $source = $segmentTagger->toExcel($segment->getSource(), $tempMap);
                $target = $segmentTagger->toExcel($segment->getTargetEdit());
                self::$excel->addSegment($segment->getSegmentNrInTask(), $source, $target);
            }
            
            // output: first send headers
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="'.$task->getTasknameForDownload('.xlsx').'"');
            header('Cache-Control: max-age=0');
            
            // .. then send the excel
            $writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx(self::$excel->getExcel());
            $writer->save('php://output');
            exit;
        }
        catch (Exception $e) {
            // throw exception 'E1137' => 'Task can not be exported as Excel-file.',
            throw new editor_Models_Excel_ExImportException('E1137',['task' => $task], $e);
        }
    }
}