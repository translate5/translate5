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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *

 /**
 * General model for Excel Metadata (= task overview and statistics). 
 * Handles all interactions with the PHPSpreadsheet (via ZfExtended_Models_Entity_ExcelExport).
 */

class editor_Models_Task_Excel_Metadata extends ZfExtended_Models_Entity_ExcelExport {
    
    /**
     * @var ZfExtended_Models_Entity_ExcelExport
     */
    protected $excelExport;
    
    /**
     * The name of the sheet that contains the 'task overview' data (aka the tasks)
     * @var string
     */
    protected static $sheetNameTaskOverview = 'task overview';
    /**
     * The name of the sheet that contains the 'meta data' (aka filtering and KPI-statistics)
     * @var string
     */
    protected static $sheetNameMeta = 'meta data';
    
    /**
     * the number of the row of the next task
     * @var integer
     */
    protected $taskRow = 2;
    
    /**
     * the number of the row of the next KPI-item
     * @var integer
     */
    protected $kpiRow = 2;
    
    /**
     * Create a new, empty excel
     * @return editor_Models_Task_Excel_Metadata
     */
    public function __construct() {
        $this->excelExport = ZfExtended_Factory::get('ZfExtended_Models_Entity_ExcelExport');
        $this->excelExport->initDefaultFormat();
    }
    
    /**
     * Init the Excel-file for our purpose.
     */
    public function initExcel() {
        // remove initial sheet
        $this->excelExport->removeWorksheetByIndex(0);
        
        // add two sheets 'task overview' and 'meta data'
        $this->excelExport->addWorksheet(self::$sheetNameTaskOverview, 0);
        $this->excelExport->addWorksheet(self::$sheetNameMeta, 1);
        
        // and init the sheets taskoverview + meta
        $this->initSheetTaskOverview();
        $this->initSheetMeta();
    }
    
    /**
     * init the sheet 'task overview'
     */
    protected function initSheetTaskOverview() {
    }
    
    /**
     * init the sheet meta data'
     */
    protected function initSheetMeta() {
        $sheet = $this->excelExport->getWorksheetByName(self::$sheetNameMeta);
        // setting write protection for the whole sheet
        $sheet->getProtection()->setSheet(true);
        
        // set font-size to "14" for the whole sheet
        $sheet->getParent()->getDefaultStyle()->applyFromArray([
            'font' => [
                'size' => '12',
            ],
        ]);
        
        // set column width
        $sheet->getColumnDimension('A')->setWidth(200);
    }
    
    /**
     * Add a KPI-item to the Excel.
     * @param string $kpiValue
     */
    public function addKPI($kpiValue) {
        $sheet = $this->excelExport->getWorksheetByName(self::$sheetNameMeta);
        $sheet->setCellValue('A'.$this->kpiRow, $kpiValue);
        $this->kpiRow++;
    }
    
    /**
     * Get the excel as Spreadsheet object
     * @return \PhpOffice\PhpSpreadsheet\Spreadsheet
     */
    public function getSpreadsheet() : \PhpOffice\PhpSpreadsheet\Spreadsheet {
        return $this->excelExport->getSpreadsheet();
    }
}

/**
 * Helper class to define a structure for the task data stored in the excel 
 */
class taskExcelMetadataTaskContainer {
    public $nr;
    public $source;
    public $target;
    public $comment;
}