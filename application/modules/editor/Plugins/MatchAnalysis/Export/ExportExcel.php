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

class editor_Plugins_MatchAnalysis_Export_ExportExcel extends ZfExtended_Models_Entity_Abstract {

    protected $dbInstanceClass = 'editor_Plugins_MatchAnalysis_Models_Db_BatchResult';

    public static function generateExcel($rows){

        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        $createdDate=null;
        $internalFuzzy=null;
        //add to all groups 'Group' sufix, php excel does not handle integer keys
        foreach ($rows as $rowKey=>$row){
            $newRows=[];
            $wordCountTotal=0;
            foreach ($row as $key=>$value){
                $newKey=$key;
                if($key=="created"){
                    $createdDate=$value;
                    continue;
                }
                if($key=="internalFuzzy"){
                    $internalFuzzy=$value;
                    continue;
                }
                if($key=="pretranslateMatchrate"){
                    continue;
                }
                //do not use resourceColor in export
                if($key=="resourceColor"){
                    continue;
                }

                if($key=="resourceName" && $value==""){
                    $value=$translate->_("Repetitions");
                }

                //change the key to $key+Group, since the excel export does not accepts numerical keys
                if(is_numeric($key)){
                    $newKey=$key.'Group';
                }

                //update the totals when collectable group is found
                if(is_numeric($key) || $key=="noMatch"){
                    $wordCountTotal+=$value;
                }
                $newRows[$newKey]=$value;
            }
            $newRows['wordCountTotal']=$wordCountTotal;
            $newRows['internalFuzzy']=$internalFuzzy;
            $newRows['created']=$createdDate;

            unset($rows[$rowKey]);
            $rows[$rowKey]=$newRows;
        }
        $spreadsheet = ZfExtended_Factory::get('ZfExtended_Models_Entity_ExcelExport');
        /* @var $excel ZfExtended_Models_Entity_ExcelExport */

        $spreadsheet->setPreCalculateFormulas(true);

        // set property for export-filename
        $spreadsheet->setProperty('filename', $translate->_('Trefferanalyse'));

        //103%, 102%, 101%. 100%, 99%-90%, 89%-80%, 79%-70%, 69%-60%, 59%-51%, 50% - 0%
        //[102=>'103',101=>'102',100=>'101',99=>'100',89=>'99',79=>'89',69=>'79',59=>'69',50=>'59'];
        $spreadsheet->setLabel('resourceName', $translate->_("Name"));
        $spreadsheet->setLabel('104Group', $translate->_("TermCollection Treffer (104%)"));
        $spreadsheet->setLabel('103Group', $translate->_("Kontext Treffer (103%)"));
        $spreadsheet->setLabel('102Group', $translate->_("Wiederholung (102%)"));
        $spreadsheet->setLabel('101Group', $translate->_("Exact-exact Treffer (101%)"));
        $spreadsheet->setLabel('100Group', '100%');
        $spreadsheet->setLabel('99Group', '99%-90%');
        $spreadsheet->setLabel('89Group', '89%-80%');
        $spreadsheet->setLabel('79Group', '79%-70%');
        $spreadsheet->setLabel('69Group', '69%-60%');
        $spreadsheet->setLabel('59Group', '59%-51%');
        $spreadsheet->setLabel('noMatch', '50%-0%');
        $spreadsheet->setLabel('wordCountTotal', $translate->_("Summe Wörter"));
        $spreadsheet->setLabel('created', $translate->_("Erstellungsdatum"));
        $spreadsheet->setLabel('internalFuzzy', $translate->_("Interner Fuzzy aktiv"));

        $rowsCount=count($rows);
        $rowIndex=$rowsCount+2;


        $sheet=$spreadsheet->getSpreadsheet()->getActiveSheet();

        $sheet->setCellValue("A".$rowIndex,$translate->_("Summe"));
        $sheet->setCellValue("B".$rowIndex, "=SUM(B2:B".($rowIndex-1).")");
        $sheet->setCellValue("C".$rowIndex, "=SUM(C2:C".($rowIndex-1).")");
        $sheet->setCellValue("D".$rowIndex, "=SUM(D2:D".($rowIndex-1).")");
        $sheet->setCellValue("E".$rowIndex, "=SUM(E2:E".($rowIndex-1).")");
        $sheet->setCellValue("F".$rowIndex, "=SUM(F2:F".($rowIndex-1).")");
        $sheet->setCellValue("G".$rowIndex, "=SUM(G2:G".($rowIndex-1).")");
        $sheet->setCellValue("H".$rowIndex, "=SUM(H2:H".($rowIndex-1).")");
        $sheet->setCellValue("I".$rowIndex, "=SUM(I2:I".($rowIndex-1).")");
        $sheet->setCellValue("J".$rowIndex, "=SUM(J2:J".($rowIndex-1).")");
        $sheet->setCellValue("K".$rowIndex, "=SUM(K2:K".($rowIndex-1).")");
        $sheet->setCellValue("L".$rowIndex, "=SUM(L2:L".($rowIndex-1).")");
        $sheet->setCellValue("M".$rowIndex, "=SUM(M2:M".($rowIndex-1).")");

        //set the cell autosize
        $spreadsheet->simpleArrayToExcel($rows,function($phpSpreadsheet){
            foreach ($phpSpreadsheet->getWorksheetIterator() as $worksheet) {

                $phpSpreadsheet->setActiveSheetIndex($phpSpreadsheet->getIndex($worksheet));

                $sheet = $phpSpreadsheet->getActiveSheet();
                $cellIterator = $sheet->getRowIterator()->current()->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(true);
                /** @var PhpOffice\PhpSpreadsheet\Cell\Cell $cell */
                foreach ($cellIterator as $cell) {
                    $sheet->getColumnDimension($cell->getColumn())->setAutoSize(true);
                }
            }
        });
    }
}

