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
class editor_Models_LanguageResources_MtUsageExporter{
    
    
    /***
     * Generate mt ussage log excel export. When no customer is provider, it will export the data for all customers.
     * 
     * @param int $customerId
     */
    public function excel(int $customerId=null) {
        $exportModel=ZfExtended_Factory::get('editor_Models_LanguageResources_MtUsageLogger');
        /* @var $exportModel editor_Models_LanguageResources_MtUsageLogger */
        $rows=$exportModel->loadByCustomer($this->getParam('customerId'));
        
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        
        $excel = ZfExtended_Factory::get('ZfExtended_Models_Entity_ExcelExport');
        /* @var $excel ZfExtended_Models_Entity_ExcelExport */
        
        // set property for export-filename
        $excel->setProperty('filename', 'Mt engine ussage export data');
        
        //TODO: find the language text and display it
        $excel->setCallback('sourceLang',function($sourceLang) use ($languages){
        });
            $excel->setCallback('targetLang',function($targetLang) use ($languages){
            });
                
                $excel->setLabel('serviceName', $translate->_("Ressource"));
                $excel->setLabel('languageResourceName', $translate->_("Name"));
                $excel->setLabel('timestamp', $translate->_("Erstellungsdatum"));
                //TODO: devide the character count with customer number
                $excel->setLabel('translatedCharacterCount', 'Übersetzte Zeichen');
                
                //set the cell autosize
                $excel->simpleArrayToExcel($rows,function($phpExcel){
                    foreach ($phpExcel->getWorksheetIterator() as $worksheet) {
                        
                        $phpExcel->setActiveSheetIndex($phpExcel->getIndex($worksheet));
                        
                        $sheet = $phpExcel->getActiveSheet();
                        $cellIterator = $sheet->getRowIterator()->current()->getCellIterator();
                        $cellIterator->setIterateOnlyExistingCells(true);
                        /** @var PHPExcel_Cell $cell */
                        foreach ($cellIterator as $cell) {
                            $sheet->getColumnDimension($cell->getColumn())->setAutoSize(true);
                        }
                    }
                });
    }
}

