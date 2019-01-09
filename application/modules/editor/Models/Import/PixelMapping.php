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


require APPLICATION_PATH.'/../library/composer/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Encapsulates the import of the pixel-mapping
 */
class editor_Models_Import_PixelMapping {
    /**
     * @var string
     */
    const PIXEL_MAPPING_XLSX = 'pixel-mapping.xlsx';
    
    /**
     * @var Spreadsheet
     */
    protected $spreadsheet = null;
    
     /**
     * import pixel-mapping.xls file
     * if exist update table LEK_pixel_mapping
     * @param editor_Models_Import_Configuration $importConfig
     */
    public function import(editor_Models_Import_Configuration $importConfig) {
        $this->importConfig = $importConfig;
        $this->loadSpreadsheet();
        $this->updateDb();
    }
    
    /**
     * load pixel-mapping-file as spreadsheet
     */
    protected function loadSpreadsheet() {
        $pixelMappingFilename = $this->importConfig->importFolder.'/'.self::PIXEL_MAPPING_XLSX;
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
        $worksheet = $this->spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow();
        if ($highestRow == 1) {
            return; // first row: headlines only
        }
        $highestColumn = $worksheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
        $pixelMappingModel = ZfExtended_Factory::get('editor_Models_PixelMapping');
        /* @var $pixelMappingModel editor_Models_PixelMapping */
        for ($row = 2; $row <= $highestRow; ++$row) { // first row: headlines only
            $values = [];
            for ($col = 1; $col <= $highestColumnIndex; ++$col) {
                $values[$col] = $worksheet->getCellByColumnAndRow($col, $row)->getValue();
            }
            $pixelMappingModel->importPixelMappingRow($values);
        }
    }
}