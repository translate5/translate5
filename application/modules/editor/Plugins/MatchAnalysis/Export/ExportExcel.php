<?php
/*
 * START LICENSE AND COPYRIGHT
 *
 *  This file is part of translate5
 *
 *  Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 *
 *  Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
 *
 *  This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 *  as published by the Free Software Foundation and appearing in the file agpl3-license.txt
 *  included in the packaging of this file.  Please review the following information
 *  to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 *  http://www.gnu.org/licenses/agpl.html
 *
 *  There is a plugin exception available for use with this release of translate5 for
 *  translate5: Please see http://www.translate5.net/plugin-exception.txt or
 *  plugin-exception.txt in the root folder of translate5.
 *
 *  @copyright  Marc Mittag, MittagQI - Quality Informatics
 *  @author     MittagQI - Quality Informatics
 *  @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
 * 			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
 *
 * END LICENSE AND COPYRIGHT
 */

/**
 * Class editor_Plugins_MatchAnalysis_Export_ExportExcel
 * Export the match analyse result into Excel
 */
class editor_Plugins_MatchAnalysis_Export_ExportExcel {

    /**
     * @var editor_Models_Task
     */
    protected editor_Models_Task $task;

    /**
     * @var ZfExtended_Zendoverwrites_Translate
     */
    protected ZfExtended_Zendoverwrites_Translate $translate;

    private array $fuzzyRanges;

    public function __construct()
    {
        $this->translate = ZfExtended_Zendoverwrites_Translate::getInstance();
    }

    public function generateExcelAndProvideDownload(editor_Models_Task $task, $rows, $filename){
        $this->task = $task;
        $this->fuzzyRanges = $task->getConfig()->runtimeOptions->plugins->MatchAnalysis->fuzzyBoundaries->toArray();
        $this->fuzzyRanges = array_reverse($this->fuzzyRanges, true);
        $this->fuzzyRanges['noMatch'] = 'noMatch'; //here we need noMatch as group too

        $data = $this->prepareDataArray($rows);

        $spreadsheet = ZfExtended_Factory::get('ZfExtended_Models_Entity_ExcelExport');
        /* @var $spreadsheet ZfExtended_Models_Entity_ExcelExport */

        $spreadsheet->setPreCalculateFormulas(true);

        // set property for export-filename
        $spreadsheet->setProperty('filename', $filename);

        $this->setLabels($spreadsheet);

        $sumRowIndex = count($data)+2;

        $sheet=$spreadsheet->getSpreadsheet()->getActiveSheet();

        $sheet->setCellValue("A".$sumRowIndex,$this->translate->_("Summe"));

        //loop over all columns containing a summable value
        $col = "B";
        $rangeCount = count($this->fuzzyRanges) + 1; //we have to add one for the sum of sum columns
        for ($i = 0; $i < $rangeCount; $i++) {
            $sheet->setCellValue($col.$sumRowIndex, '=SUM('.$col.'2:'.$col.($sumRowIndex-1).")");
            $col++; //increment the column characters
        }

        //set the cell autosize
        $spreadsheet->simpleArrayToExcel($data,function($phpSpreadsheet){
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

    /**
     * @param $rows
     * @throws Zend_Exception
     */
    protected function prepareDataArray($rows)
    {
        //add to all groups 'Group' sufix, php excel does not handle integer keys
        $result = [];
        foreach ($rows as $row){
            $unitCountTotal = 0;

            //the order in the newRows array defines the result order in the spreadsheet
            $newRow = [
                'resourceName' => empty($row['resourceName']) ? $this->translate->_("Repetitions") : $row['resourceName']
            ];

            //loop over the fuzzy ranges and get the corresponding content
            foreach($this->fuzzyRanges as $begin => $end) {
                if(array_key_exists($begin, $row)) {
                    //change the key to $key+Group, since the Excel export does not accept numerical keys
                    $key = is_numeric($begin) ? $begin.'Group' : $begin;
                    $newRow[$key] = $row[$begin];
                    $unitCountTotal += $row[$begin];
                }
            }

            $newRow['unitCountTotal'] = $unitCountTotal;
            $newRow['internalFuzzy'] = $row['internalFuzzy'];
            $newRow['created'] = $row['created'];

            $result[] = $newRow;
        }
        return $result;
    }

    protected function setLabels(ZfExtended_Models_Entity_ExcelExport $spreadsheet)
    {
        $spreadsheet->setLabel('resourceName', $this->translate->_("Name"));

        $beginners = array_keys($this->fuzzyRanges);
        //remove the nomatch element itself to get the last and smallest begin value
        array_pop($beginners);
        $noMatchEnd = (int) end($beginners) - 1;

        foreach($this->fuzzyRanges as $begin => $end) {
            if($begin == 'noMatch') {
                //just keep $begin as it is
                $label = $noMatchEnd.'%-0%';
            }
            elseif($begin === $end) { //must come after noMatch if, since on noMatch is begin == end too
                //if the range is a single element range, some of the special texts may be used:
                $label = $this->getSingleElementRangeLabel($begin);
                $begin .= 'Group';
            }

            else{
                //since matchrate groups are DESC from left to right, we use the labels also in that direction
                $label = sprintf('%d%%-%d%%', $end, $begin);
                $begin .= 'Group';
            }
            $spreadsheet->setLabel($begin, $label);
        }

        $spreadsheet->setLabel('unitCountTotal', $this->translate->_("Summe"));
        $spreadsheet->setLabel('created', $this->translate->_("Erstellungsdatum"));
        $spreadsheet->setLabel('internalFuzzy', $this->translate->_("Interner Fuzzy aktiv"));
    }

    private function getSingleElementRangeLabel(int $match): string {
        switch ($match) {
            case 104:
                return $this->translate->_("TermCollection Treffer (104%)");
            case 103:
                return $this->translate->_("Kontext Treffer (103%)");
            case 102:
                return $this->translate->_("Wiederholung (102%)");
            case 101:
                return $this->translate->_("Exact-exact Treffer (101%)");
            default :
                return sprintf('%d%%', $match);
        }
    }
}

