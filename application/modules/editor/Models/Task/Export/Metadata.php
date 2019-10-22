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
 */
class editor_Models_Task_Export_Metadata {
    /**
     * @var editor_Models_Task_Excel_Metadata
     */
    protected $excelMetadata;
    
    /**
     * @var array
     */
    protected $tasks;
    
    /**
     * @var array
     */
    protected $kpiStatistics;
    
    /**
     * @var ZfExtended_Logger
     */
    protected $log;
    
    public function __construct() {
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
     * Set KPI-statistics.
     * @param array$rows
     */
    public function setKpiStatistics(array $kpiStatistics) {
        $this->kpiStatistics = $kpiStatistics;
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
        $this->excelMetadata->initExcel();
        
        // add all the data
        
        // .. then send the excel
        $writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx($this->excelMetadata->getSpreadsheet());
        $writer->save($fileName);
    }
    
    /**
     * 
     * @return string
     */
    protected function getFilenameForDownload() {
        return 'metadataExport_'.date("Y-m-d h:i:sa").'.xlsx';
    }
}