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
 * Export given tasks, their filtering and their key performance indicators (KPI) as an Excel-file.
 * This class should not directly interact with the PHPSpreadsheet, this is done via editor_Models_Task_Excel_Metadata.
 * TODO: Achieve this completely by refactoring export(), exportAsDownload() and exportAsFile().
 */
class editor_Models_Task_Export_Metadata {
    /**
     * @var editor_Models_Task_Excel_Metadata
     */
    protected $excelMetadata;
    
    /**
     * Tasks as currently filtered by the user.
     * @var array
     */
    protected $tasks;
    
    /**
     * Filters currently applied by the user.
     * @var array
     */
    protected $filters;
    
    /**
     * Visible columns of the task-grid (order and names).
     * @var array
     */
    protected $columns;
    
    /**
     * Key Performance Indicators (KPI) for the current tasks.
     * @var array
     */
    protected $kpiStatistics;
    
    /**
     * @var ZfExtended_Zendoverwrites_Translate
     */
    protected $translate;
    
    /**
     * @var ZfExtended_Logger
     */
    protected $log;
    
    public function __construct() {
        $this->translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        $this->log = Zend_Registry::get('logger')->cloneMe('editor.task.excel.metadata');
    }
    
    /**
     * Set tasks.
     * @param array $rows
     */
    public function setTasks(array$rows) {
        $this->tasks = $rows;
    }
    
    /**
     * Set the filters that the user applied in the task overview.
     * @param array $rows
     */
    public function setFilters(array $filters) {
        $this->filters = $filters;
    }
    
    /**
     * Set the columns that are currently visible in the task overview.
     * @param array $rows
     */
    public function setColumns(array $columns) {
        $this->columns = $columns;
    }
    
    /**
     * Set KPI-statistics.
     * @param array $rows
     */
    public function setKpiStatistics(array $kpiStatistics) {
        $this->kpiStatistics = $kpiStatistics;
    }
    
    /**
     * Get a KPI-value by the indicator's name.
     * @param string $name
     * @return string
     */
    protected function getKpiValueByName(string $name) {
        return $this->kpiStatistics[$name];
    }
    
    /**
     * export xls from stored task, returns true if file was created
     * @param string $fileName where the XLS should go to
     * @return bool
     */
    public function exportAsFile(string $fileName): bool {
        try {
            $this->export($fileName);
            return true;
        }
        catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * provides the excel as download to the browser
     */
    public function exportAsDownload(): void {
        // output: first send headers
        if(!$this->exportAsFile('php://output')) {
            throw new editor_Models_Task_Excel_MetadataException('E1170');
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.$this->getFilenameForDownload().'"');
        header('Cache-Control: max-age=0');
        exit;
    }
    
    /**
     * does the export
     * @param string $fileName where the XLS should go to
     */
    protected function export(string $fileName): void {
        $this->excelMetadata = ZfExtended_Factory::get('editor_Models_Task_Excel_Metadata');
        $this->excelMetadata->initExcel($this->columns);
        
        // add data: filters
        $this->excelMetadata->addMetadataHeadline($this->translate->_('Filter'));
        if (count($this->filters) == 0) {
            $this->filters[] = (object)['property' =>' ', 'operator' => ' ', 'value' => '-'];
        }
        foreach ($this->filters as $filter) {
            $this->excelMetadata->addFilter($filter);
        }
        
        // add data: KPI
        $this->excelMetadata->addMetadataHeadline($this->translate->_('KPI'));
        $this->excelMetadata->addKPI($this->renderKpiAverageProcessingTime());
        $this->excelMetadata->addKPI($this->renderKpiExcelExportUsage());
        
        // add data: tasks
        foreach ($this->tasks as $task) {
            $this->excelMetadata->addTask($task);
        }
        // what we added latest, will be the first sheet when opening the excel-file.
        
        // finalize the layout
        $this->excelMetadata->setColWidth();
        
        // .. then send the excel
        $writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx($this->excelMetadata->getSpreadsheet());
        $writer->save($fileName);
    }
    
    /**
     * KPI: Render translated version of the Average Processing Time.
     * @return string
     */
    protected function renderKpiAverageProcessingTime() : string {
        $average = $this->getKpiValueByName('averageProcessingTime');
        if ($average != '-') {
            $average = sprintf($this->translate->_('%0.0f Tage'), round($average, 0));
        }
        return $this->translate->_('Ø Bearbeitungszeit Lektor') . ': ' . $average;
    }
    
    /**
     * KPI: Render translated version of the Excel Export Usage.
     * @return string
     */
    protected function renderKpiExcelExportUsage() : string {
        $percentage = $this->getKpiValueByName('excelExportUsage');
        return $percentage . ' ' . $this->translate->_('Excel-Export Nutzung');
    }
    
    /**
     * 
     * @return string
     */
    protected function getFilenameForDownload() {
        return 'metadataExport_'.date("Y-m-d h:i:sa").'.xlsx';
    }
}