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
 * General model for using PhpSpreadsheet eg for Excel-Exports.
 * (Place basic methods here instead of repeating them each time.)
 */

require_once APPLICATION_PATH.'/../library/PhpSpreadsheet/vendor/autoload.php';


class editor_Models_PHPSpreadsheet {
    
    /**
     * Container to hold the excel-object aka PhpOffice\PhpSpreadsheet\Spreadsheet
     * @var \PhpOffice\PhpSpreadsheet\Spreadsheet
     */
    protected $excel = NULL;
    
    /**
     * Create a new, empty excel
     * @return editor_Models_Task_Excel_Metadata
     */
    public static function createNewExcel() : editor_Models_Task_Excel_Metadata {
        $tempExcel = ZfExtended_Factory::get('editor_Models_Task_Excel_Metadata',[],false);
        /* @var $tempExcel editor_Models_Task_Excel_Metadata */
        
        // create a new spreadsheet object
        $tempExcel->excel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $tempExcel->initDefaultFormat();
        
        // remove initial sheet
        $tempExcel->excel->removeSheetByIndex(0);
        
        // return the editor_Models_Task_Excel_Metadata object
        return $tempExcel;
    }
    
    
    /**
     * Get the excel as Spreadsheet object
     * @return \PhpOffice\PhpSpreadsheet\Spreadsheet
     */
    public function getExcel() : \PhpOffice\PhpSpreadsheet\Spreadsheet {
        return $this->excel;
    }
    
    /**
     * set global document format settings
     */
    protected function initDefaultFormat() {
        $this->excel->getDefaultStyle()->getAlignment()
        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP)
        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT)
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
     * Returns the worksheet of the given name
     * @param string $sheetName
     * @return PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
     */
    protected function getSheetByName($sheetName) {
        $this->excel->setActiveSheetIndexByName($sheetName);
        return $this->excel->getActiveSheet();
    }
}