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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *

 /**
 * General model for Excel ex- and im-ports.
 * Will be used by the models under ../Export/Excel.php respectively ../Import/Excel.php
 * TODO: refactor (= implement with ZfExtended_Models_Entity_ExcelExport)
 */
class editor_Models_Excel_ExImport {
    
    /**
     * the taskGuid the excel belongs to
     * @var string
     */
    protected $taskGuid = NULL;
    
    /**
     * the taskName the excel belongs to
     * @var string
     */
    protected $taskName = NULL;
    
    /**
     * the mail adress of the pm of the task this excel belongs to
     * @var string
     */
    protected $taskMailPm = NULL;
    
    /**
     * Container to hold the excel-object aka PhpOffice\PhpSpreadsheet\Spreadsheet
     * @var \PhpOffice\PhpSpreadsheet\Spreadsheet
     */
    protected $excel = NULL;
    
    /**
     * The name of the sheet that contains the 'review job' data (aka the segments)
     * @var string
     */
    protected static $sheetNameJob = 'review job';
    /**
     * The name of the sheet that contains the 'meta data' (aka pmMail, taskGuid, -Name)
     * @var string
     */
    protected static $sheetNameMeta = 'meta data';
    
    /**
     * the number of the row the next segment:
     * - will be written by ->addSegment()
     * - or will be read out by ->getSegments()
     * @var integer
     */
    protected $segmentRow = 2;
    
    /**
     * @var ZfExtended_Logger
     */
    protected $log;
    
    /**
     * Class should not be used with new editor_Models_Excel_ExImport()
     * You should use
     * ::createNewExcel($task) or
     * ::loadFromExcel($file)
     * to get an instance of the class
     */
    protected function __construct() {
        $this->log = Zend_Registry::get('logger')->cloneMe('editor.task.exceleximport');
    }
    
    /**
     * Create a new, empty excel
     * @param editor_Models_Task $task
     * @return editor_Models_Excel_ExImport
     */
    public static function createNewExcel(editor_Models_Task $task) : editor_Models_Excel_ExImport {
        $tempExImExcel = ZfExtended_Factory::get('editor_Models_Excel_ExImport',[],false);
        /* @var editor_Models_Excel_ExImport $tempExImExcel */
        $tempExImExcel->__construct();
        
        // init class properties
        $tempExImExcel->taskGuid = $task->getTaskGuid();
        $tempExImExcel->taskName = $task->getTaskName();
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        $user->loadByGuid($task->getPmGuid());
        $tempExImExcel->taskMailPm = $user->getEmail();
        
        
        
        // create a new spreadsheet object
        $tempExImExcel->excel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $tempExImExcel->initDefaultFormat();
        
        // add two sheets 'review job' and 'meta data'
        $tempExImExcel->excel->removeSheetByIndex(0); // remove initial sheet
        $tempExImExcel->addSheet(self::$sheetNameJob, 0); // add sheet 'review job'
        $tempExImExcel->addSheet(self::$sheetNameMeta, 1); // add sheet 'meta data'
        
        // and init the sheets job + meta
        $tempExImExcel->initSheetJob();
        $tempExImExcel->initSheetMeta();
        
        
        // return the editor_Models_Excel_ExImport object
        return $tempExImExcel;
    }
    
    /**
     * Write the segment informations into the 'review job' sheet
     * the row this informations are written is $this->segmentRow
     * @param int $nr
     * @param string $source
     * @param string $target
     */
    public function addSegment(int $nr, string $source, string $target) {
        $sheet = $this->getSheetJob();
        $sheet->setCellValue('A'.$this->segmentRow, $nr);
        // for the following fields setCellValueExplicit() is used. Else this fields will be interpreted as formula fields if a segment starts with "="
        $sheet->setCellValueExplicit('B'.$this->segmentRow, $source, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('C'.$this->segmentRow, $target, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        
        // disable write protection for fields 'target' and 'comment'
        $sheet->getStyle('C'.$this->segmentRow)->getProtection()->setLocked(\PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_UNPROTECTED);
        $sheet->getStyle('D'.$this->segmentRow)->getProtection()->setLocked(\PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_UNPROTECTED);
        
        $this->segmentRow++;
    }
    
    
    /**
     * Get the excel as Spreadsheet object
     * @return \PhpOffice\PhpSpreadsheet\Spreadsheet
     */
    public function getExcel() : \PhpOffice\PhpSpreadsheet\Spreadsheet {
        // set sheet 'review job' as active sheet
        $this->getSheetJob();
        return $this->excel;
    }
    
    
    /**
     * Load an excel from a file
     * @TODO
     *
     * @param string $filename
     * @return editor_Models_Excel_ExImport
     */
    public static function loadFromExcel(string $filename) : editor_Models_Excel_ExImport {
        $tempExImExcel = ZfExtended_Factory::get('editor_Models_Excel_ExImport', [], false);
        /* @var editor_Models_Excel_ExImport $tempExImExcel */
        $tempExImExcel->__construct();
        
        // load excel
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $tempExImExcel->excel = $reader->load($filename);
        
        // load relevant (meta-)informations (see also ->initSheetMeta())
        $sheet = $tempExImExcel->getSheetMeta();
        $tempExImExcel->taskGuid = $sheet->getCell('B6')->getValue();
        $tempExImExcel->taskName = $sheet->getCell('B5')->getValue();
        $tempExImExcel->taskMailPm = $sheet->getCell('B1')->getValue();
        
        // return the editor_Models_Excel_ExImport object
        return $tempExImExcel;
    }
    
    /**
     * get the taskGuid as stored in the excel
     * @return string
     */
    public function getTaskGuid() : string {
        return $this->taskGuid;
    }
    
    /**
     * get the taskName as stored in the excel
     * @return string
     */
    public function getTaskName() : string {
        return $this->taskName;
    }
    
    /**
     * get the mail of the task PM as stored in the excel
     * @return string
     */
    public function getTaskMailPm() : string {
        return $this->taskMailPm;
    }
    
    /**
     * Read all segments in the excel and return the informations as a list of
     * excelExImportSegmentContainer objects
     *
     * @return [excelExImportSegmentContainer]
     */
    public function getSegments() : array {
        // reset $this->segmentRow
        $this->segmentRow = 2;
        $tempReturn = [];
        $sheet = $this->getSheetJob();
        
        // read the excel rows until the field which contains the task-nr is empty
        while ($nr = $sheet->getCell('A'.$this->segmentRow)->getValue()) {
            $tempSegment = new excelExImportSegmentContainer();
            $tempSegment->nr = $nr;
            $tempSegment->source = $sheet->getCell('B'.$this->segmentRow)->getValue();
            $tempSegment->target = $sheet->getCell('C'.$this->segmentRow)->getValue();
            $tempSegment->comment = trim($sheet->getCell('D'.$this->segmentRow)->getValue());
            $tempReturn[] = $tempSegment;
            
            $this->segmentRow++;
        }
        return $tempReturn;
        
    }
    
    /**
     * set global document format settings
     */
    protected function initDefaultFormat() {
        $this->excel->getDefaultStyle()->getAlignment()
            // vertical align: top;
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP)
            // text-align: left;
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT)
            // auto-wrap text to new line;
            ->setWrapText(TRUE);
        // @TODO: add some padding to all fields... but how??
    }
    
    /**
     * Add a new sheet to the excel
     * @param string $name
     * @param int $index
     */
    protected function addSheet(string $name, int $index) : void {
        $tempSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($this->excel, $name);
        $tempSheet->getDefaultColumnDimension()->setAutoSize(TRUE); // does not work propper in Libre-Office. With Microsoft-Office everything is OK.
        $this->excel->addSheet($tempSheet, $index);
    }
    
    /**
     * Returns the worksheet which contains the job data (the segments)
     * @return PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
     */
    protected function getSheetJob() {
        $this->excel->setActiveSheetIndexByName(self::$sheetNameJob);
        return $this->excel->getActiveSheet();
    }
    /**
     * Returns the worksheet which contains the meta data (taskGuid etc.)
     * @return PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
     */
    protected function getSheetMeta() {
        $this->excel->setActiveSheetIndexByName(self::$sheetNameMeta);
        return $this->excel->getActiveSheet();
    }
    
    
    /**
     * init the sheet 'review job'
     */
    protected function initSheetJob() {
        $sheet = $this->getSheetJob();
        // setting write protection for the whole sheet
        $sheet->getProtection()->setSheet(true);
        
        // set font-size to "14" for the whole sheet
        $sheet->getParent()->getDefaultStyle()->applyFromArray([
                        'font' => [
                                        'size' => '14',
                        ],
        ]);
        
        // set column width
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(70);
        $sheet->getColumnDimension('C')->setWidth(70);
        $sheet->getColumnDimension('D')->setWidth(50);
        
        // write fieldnames in header
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Source');
        $sheet->setCellValue('C1', 'Target (Please enter your changes in this column)');
        $sheet->setCellValue('D1', 'Comment');
        $sheet->getStyle('A1:D1')->getFont()->setBold(TRUE);
    }
    
    /**
     * init the sheet meta data'
     */
    protected function initSheetMeta() {
        $sheet = $this->getSheetMeta();
        // setting write protection for the whole sheet
        $sheet->getProtection()->setSheet(true);
        
        // set column width
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(70);
        
        // write all needed informations
        $sheet->setCellValue('A1', 'E-Mail:');
        $sheet->setCellValue('B1', $this->taskMailPm);
        $sheet->setCellValue('A2', 'Please send Excel file back to the above mail address');
        $sheet->getStyle('A1:B2')->getFont()->setBold(TRUE);
        $sheet->getStyle('A2')->getAlignment()->setWrapText(FALSE);
        
        $sheet->setCellValue('A5', 'Task-Name:');
        $sheet->setCellValue('B5', $this->taskName);
        $sheet->setCellValue('A6', 'Task-Guid:');
        $sheet->setCellValue('B6', $this->taskGuid);
        
        //deactivate writing for CELL B6 (taskGuid). Only enabled for development
        //$sheet->getStyle('B6')->getProtection()->setLocked(\PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_UNPROTECTED);
    }
}

/**
 * Helper class to define a structure for the segment data stored in the excel
 */
class excelExImportSegmentContainer {
    public $nr;
    public $source;
    public $target;
    public $comment;
}