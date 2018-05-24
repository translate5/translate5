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
 */
class editor_Plugins_MatchAnalysis_MatchAnalysisController extends ZfExtended_RestController {

    protected $entityClass = 'editor_Plugins_MatchAnalysis_Models_MatchAnalysis';

    /**
     * @var editor_Plugins_MatchAnalysis_Models_MatchAnalysis
     */
    protected $entity;
    
    public function indexAction(){
        $params=$this->getAllParams();
        $this->view->rows=$this->entity->loadByBestMatchRate($params['taskGuid']);
    }
    
    public function exportAction(){
        $params=$this->getAllParams();

        //load the export data
        $rows=$this->entity->loadByBestMatchRate($params['taskGuid'],true);
        
        //sort the data
        krsort($rows);
        
        $newRows=[];
        foreach ($rows as $key=>$value){
            $newKey=$key;
            if(is_numeric($key)){
                $newKey=$key.'Group';
            }
            $newRows[$newKey]=$value;
        }
        unset($rows);
        
        $excel = ZfExtended_Factory::get('ZfExtended_Models_Entity_ExcelExport');
        /* @var $excel ZfExtended_Models_Entity_ExcelExport */
        
        //TODO: translate the strings 
        // set property for export-filename
        $excel->setProperty('filename', 'Match analysis');
        
        //103%, 102%, 101%. 100%, 99%-90%, 89%-80%, 79%-70%, 69%-60%, 59%-51%, 50% - 0%
        //[102=>'103',101=>'102',100=>'101',99=>'100',89=>'99',79=>'89',69=>'79',59=>'69',50=>'59'];
        $excel->setLabel('103Group', 'Context match (103%)');
        $excel->setLabel('102Group', 'Repetition (102%)');
        $excel->setLabel('101Group', 'Exact-exact match (101%)');
        $excel->setLabel('100Group', '100%');
        
        $excel->setLabel('99Group', '99%-90%');
        $excel->setLabel('89Group', '89%-80%');
        $excel->setLabel('79Group', '79%-70%');
        $excel->setLabel('69Group', '69%-60%');
        $excel->setLabel('59Group', '59%-51%');
        $excel->setLabel('noMatch', '50%-0%');
        
        $excel->setLabel('wordCountTotal', 'Words count');
        $excel->setLabel('created', 'Creation date');
        
        //set the cell autosize
        $excel->simpleArrayToExcel([$newRows],function($phpExcel) {
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