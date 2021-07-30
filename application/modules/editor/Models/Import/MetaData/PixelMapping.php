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

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Encapsulates the import of the pixel-mapping
 */
class editor_Models_Import_MetaData_PixelMapping implements editor_Models_Import_MetaData_IMetaDataImporter {
    /**
     * @var string
     */
    const PIXEL_MAPPING_XLSX = 'pixel-mapping.xlsx';
    
    /**
     * @var array
     * The order of columns in the spreadsheet must not be changed!
     * (Example: https://confluence.translate5.net/display/BUS/Length+Restrictions+and+Pixel-Mapping)
     */
    protected $colsNamesAndOrder = ['font', 'fontsize', 'unicodeChar', 'pixelWidth'];
    
    /**
     * @var string
     */
    protected $importPath;
    
    /**
     * @var Spreadsheet
     */
    protected $spreadsheet = null;
    
    /**
     * @var editor_Models_Task
     */
    protected $task = null;
    
    /**
     * Container for ignored lines from Excel file
     * @var array
     */
    protected $ignoredLines = [];
    
    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_MetaData_IMetaDataImporter::import()
     */
    public function import(editor_Models_Task $task, editor_Models_Import_MetaData $meta) {
        $this->task = $task;
        $this->importPath = $meta->getImportPath();
        $this->importFromSpreadsheet();
    }
    
     /**
     * import pixel-mapping.xls file
     * if exist update table LEK_pixel_mapping
     */
    public function importFromSpreadsheet() {
        $this->loadSpreadsheet();
        $this->updateDb();
        $this->logIgnoredLines();
    }
    
    /**
     * load pixel-mapping-file as spreadsheet
     */
    protected function loadSpreadsheet() {
        $pixelMappingFilename = $this->importPath.DIRECTORY_SEPARATOR.self::PIXEL_MAPPING_XLSX;
        if (file_exists($pixelMappingFilename)) {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader("Xlsx");
            $reader->setReadDataOnly(true);
            $this->spreadsheet = $reader->load($pixelMappingFilename);
        }
    }
    
    /**
     * update database according to the spreadsheet
     */
    protected function updateDb () {
        if (is_null($this->spreadsheet)) {
            return;
        }
        $taskGuid = $this->task->getTaskGuid();
        $worksheet = $this->spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow();
        if ($highestRow == 1) {
            return; // first row: headlines only
        }
        $pixelMappingModel = ZfExtended_Factory::get('editor_Models_PixelMapping');
        /* @var $pixelMappingModel editor_Models_PixelMapping */
        for ($row = 2; $row <= $highestRow; ++$row) { // first row: headlines only
            $values = [];
            $values['taskGuid'] = $taskGuid;
            $values['fileId']   = null;
            $oneColWasEmpty = false;
            $col = 1;
            foreach ($this->colsNamesAndOrder as $colName) {
                $values[$colName] = $worksheet->getCellByColumnAndRow($col, $row)->getValue();
                $oneColWasEmpty = $oneColWasEmpty || (strlen($values[$colName]) == 0);
                $col++;
            }
            if($oneColWasEmpty) {
                array_unshift($values, $row);
                $this->ignoredLines[] = join(', ', $values);
                continue;
            }
            $pixelMappingModel->insertPixelMappingRow($taskGuid, null, $values['font'], $values['fontsize'], $values['unicodeChar'], $values['pixelWidth']);
        }
    }
    
    /**
     * Log non importable lines from Excel file
     */
    protected function logIgnoredLines() {
        if(empty($this->ignoredLines)) {
            return;
        }
        $logger = Zend_Registry::get('logger')->cloneMe('editor.import.metadata.pixelmapping');
        /* @var $logger ZfExtended_Logger */
        $msg = 'Pixel-Mapping: ignored one ore more lines of the excel due one or more empty columns.';
        $logger->warn('E1096', $msg, [
            'task' => $this->task,
            'ignoredLines' => "\n".join("\n", $this->ignoredLines),
        ]);
    }
}