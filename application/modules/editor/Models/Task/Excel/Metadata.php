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
 * General model for Excel Metadata (= task overview and statistics). 
 * Handles all interactions with the PHPSpreadsheet (via ZfExtended_Models_Entity_ExcelExport).
 */

class editor_Models_Task_Excel_Metadata extends ZfExtended_Models_Entity_ExcelExport {
    
    /**
     * general max width for all cols in the Excel.
     * @var int
     */
    CONST COL_MAX_WIDTH = 40; // just test what looks best
    
    /**
     * @var ZfExtended_Models_Entity_ExcelExport
     */
    protected $excelExport;
    
    /**
     * The name of the sheet that contains the 'task overview' data (aka the tasks)
     * @var string
     */
    protected $sheetNameTaskOverview;
    
    /**
     * The name of the sheet that contains the 'meta data' (aka filtering and KPI-statistics)
     * @var string
     */
    protected $sheetNameMetadata;
    
    /**
     * the number of the row of the next task
     * @var integer
     */
    protected $taskRow = 2;
    
    /**
     * columns to show for the tasks
     * @var array
     */
    protected $taskColumns = [];
    
    /**
     * the number of the row in the metadata-sheet
     * @var integer
     */
    protected $metadataRow = 1;
    
    /**
     * @var ZfExtended_Zendoverwrites_Translate
     */
    protected $translate;
    
    /**
     * Create a new, empty excel
     * @return editor_Models_Task_Excel_Metadata
     */
    public function __construct() {
        $this->excelExport = ZfExtended_Factory::get('ZfExtended_Models_Entity_ExcelExport');
        $this->excelExport->initDefaultFormat();
        $this->translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        $this->sheetNameTaskOverview = $this->translate->_('Aufgaben');
        $this->sheetNameMetadata = $this->translate->_('Meta-Daten');
    }
    
    /**
     * Init the Excel-file for our purpose.
     * @param array $columns
     */
    public function initExcel($columns) {
        $this->taskColumns = $columns;
        
        // remove initial sheet
        $this->excelExport->removeWorksheetByIndex(0);
        
        // add two sheets 'task overview' and 'meta data'
        $this->excelExport->addWorksheet($this->sheetNameTaskOverview, 0);
        $this->excelExport->addWorksheet($this->sheetNameMetadata, 1);
        
        // and init the sheets taskoverview + meta
        $this->initSheetTaskOverview();
        $this->initSheetMeta();
    }
    
    /**
     * Init the sheet 'task overview'.
     */
    protected function initSheetTaskOverview() {
        $sheet = $this->excelExport->getWorksheetByName($this->sheetNameTaskOverview);
        
        // set font-size to "12" for the whole sheet
        $sheet->getParent()->getDefaultStyle()->applyFromArray([
            'font' => [
                'size' => '12',
            ],
        ]);
        
        // write fieldnames in header, set their font to bold, set their width to auto
        $sheetCol = 'A';
        $taskModel = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $taskModel editor_Models_Task */
        $taskGridTextCols = $taskModel::getTaskGridTextCols();
        foreach ($this->taskColumns as $key => $colName) {
            if (array_key_exists($colName, $taskGridTextCols)) { // Not all column-names in the taskGrid have a translation.
                $colHeadline = $this->translate->_($taskGridTextCols[$colName]);
            } else {
                $colHeadline = $colName;
            }
            $sheet->setCellValue($sheetCol.'1', ucfirst($colHeadline));
            $sheet->getStyle($sheetCol.'1')->getFont()->setBold(true);
            $sheet->getColumnDimension($sheetCol)->setAutoSize(true);
            $sheetCol++; //inc alphabetical
        }
    }
    
    /**
     * init the sheet 'meta data'
     */
    protected function initSheetMeta() {
        $sheet = $this->excelExport->getWorksheetByName($this->sheetNameMetadata);
        
        // set font-size to "12" for the whole sheet
        $sheet->getParent()->getDefaultStyle()->applyFromArray([
            'font' => [
                'size' => '12',
            ],
        ]);
        
        // set column width
        $sheet->getColumnDimension('A')->setWidth(200);
    }
    
    /**
     * Add a task to the Excel. The result should look exactly as in the taskGrid.
     * @param array $task
     */
    public function addTask($task) {
        $sheet = $this->excelExport->getWorksheetByName($this->sheetNameTaskOverview);
        $sheetCol = 'A';
        foreach ($this->taskColumns as $key => $colName) {
            if (!array_key_exists($colName, $task)) {
                // eg taskassoc is not always set for every task
                $sheetCol++;
                continue;
            }
            switch ($colName) {
                case 'customerId':
                    $customer = ZfExtended_Factory::get('editor_Models_Customer_Customer');
                    /* @var $customer editor_Models_Customer_Customer */
                    $customer->load($task['customerId']);
                    $value = $customer->getName();
                    break;
                case 'orderdate':
                case 'enddate':
                    $value = \PhpOffice\PhpSpreadsheet\Shared\Date::stringToExcel($task[$colName]);

                    $sheet->getStyle($sheetCol.$this->taskRow)
                        ->getNumberFormat()
                        ->setFormatCode(
                            \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_YYYYMMDD
                        );

                    break;
                case 'relaisLang':
                case 'sourceLang':
                case 'targetLang':
                    if ($task[$colName] == 0) {
                        // relaisLang might not be set = ok
                        $value = '';
                    } else {
                        $languages = ZfExtended_Factory::get('editor_Models_Languages');
                        /* @var $languages editor_Models_Languages */
                        try {
                            $languages->load($task[$colName]);
                            $value = $languages->getLangName() . ' (' . $languages->getRfc5646() . ')';
                        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
                            $value = '- notfound -';
                        }
                    }
                    break;
                case 'state':
                        $workflow = ZfExtended_Factory::get('editor_Workflow_Manager')->getActive($task['taskGuid']);
                    try {
                        $workflow = ZfExtended_Factory::get('editor_Workflow_Manager')->getActive($task['taskGuid']);
                    }
                    catch (editor_Workflow_Exception $e) {
                        //normally that means that the workflow was not found, so we just use the default one
                        $workflow = ZfExtended_Factory::get('editor_Workflow_Manager')->get('default');
                    }
                    /* @var $workflow editor_Workflow_Default */
                    $states = $workflow->getStates();
                    $labels = $workflow->getLabels(true);
                    $value = (array_search($task['state'], $states) !== false) ? $labels[array_search($task['state'], $states)] : $task['state'];
                    break;
                case 'workflow':
                    $value = $task['workflow'] . ' (' . $task['workflowStepName'] . ')';
                    break;
                case 'taskassocs':
                    $allTaskassocs= $task['taskassocs'];
                    $values = [];
                    foreach ($allTaskassocs as $assoc) {
                        $values[] = $assoc['name'] . ' (' . $assoc['serviceName'] . ')';
                    }
                    $value = count($allTaskassocs) . ': ' . implode(', ', $values);
                    break;
                default:
                    $value = $task[$colName];
                    break;
            }
            $sheet->setCellValue($sheetCol.$this->taskRow, $value);
            $sheetCol++;
        }
        $this->taskRow++;
    }
    
    /**
     * Add a headline to the metadata-sheet.
     * @param string $filter
     */
    public function addMetadataHeadline($headline) {
        $sheet = $this->excelExport->getWorksheetByName($this->sheetNameMetadata);
        $sheet->setCellValue('A'.$this->metadataRow, $headline);
        $sheet->getStyle('A'.$this->metadataRow)->getFont()->setBold(true);
        $this->metadataRow++;
    }
    
    /**
     * Add filter-setting to the Excel.
     * @param string $filter
     */
    public function addFilter($filter) {

        $sheet = $this->excelExport->getWorksheetByName($this->sheetNameMetadata);
        switch (true) {
            case is_array($filter->value):
                $value = implode(', ',$filter->value);
            break;
            default:
                $value = $filter->value;
            break;
        }
        $sheet->setCellValue('A'.$this->metadataRow, $filter->property . ' ' . $filter->operator . ' ' . $value);
        $this->metadataRow++;
    }
    
    /**
     * Add a KPI-item to the Excel.
     * @param string $kpiValue
     */
    public function addKPI($kpiValue) {
        $sheet = $this->excelExport->getWorksheetByName($this->sheetNameMetadata);
        $sheet->setCellValue('A'.$this->metadataRow, $kpiValue);
        $this->metadataRow++;
    }
    
    /**
     * Get the excel as Spreadsheet object
     * @return \PhpOffice\PhpSpreadsheet\Spreadsheet
     */
    public function getSpreadsheet() : \PhpOffice\PhpSpreadsheet\Spreadsheet {
        return $this->excelExport->getSpreadsheet();
    }
    
    /**
     * Set autowidth with maximum for all columns in the Excel.
     */
    public function setColWidth() {
        // https://github.com/PHPOffice/PhpSpreadsheet/issues/275
        foreach ($this->excelExport->getAllWorksheets() as $sheet) {
            $sheet->calculateColumnWidths();
            foreach ($sheet->getColumnDimensions() as $colDim) {
                if (!$colDim->getAutoSize()) {
                    continue;
                }
                $colWidth = $colDim->getWidth();
                if ($colWidth > self::COL_MAX_WIDTH) {
                    $colDim->setAutoSize(false);
                    $colDim->setWidth(self::COL_MAX_WIDTH);
                }
            }
        }
    }
}