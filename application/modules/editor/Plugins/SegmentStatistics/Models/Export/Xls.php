<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

require_once('ZfExtended/ThirdParty/PHPExcel/PHPExcel.php');

/**
 * Default Model for Plugin SegmentStatistics
 */
class editor_Plugins_SegmentStatistics_Models_Export_Xls extends editor_Plugins_SegmentStatistics_Models_Export_Abstract {
    const FILE_SUFFIX='.xlsx';
    const TPL_PREFIX='STAT.';
    
    /**
     * Most of stats affect source field only, so define it here:
     * @var string
     */
    const FIELD_SOURCE='source';

    /**
     * converted statistics data
     * @var array
     */
    protected $data = array();
    
    /**
     * @var PHPExcel
     */
    protected $xls;
    
    public function init(editor_Models_Task $task, stdClass $statistics, array $workerParams) {
        parent::init($task, $statistics, $workerParams);
        
        $config = Zend_Registry::get('config')->runtimeOptions->plugins->SegmentStatistics;
        
        settype($this->statistics->filesImport, 'array');
        settype($this->statistics->filesExport, 'array');
        $this->convertToJsonStyleIndex($this->statistics->filesImport, 'import');
        $this->convertToJsonStyleIndex($this->statistics->filesExport, 'export');
        
        if($this->type == self::TYPE_IMPORT) {
            $tpl = $config->xlsTemplateImport;
        }
        else {
            $tpl = $config->xlsTemplateExport;
        }
        
        $this->xls = PHPExcel_IOFactory::load($tpl);
        $this->fillSheetOverview();
        $this->fillSheetSummary();
        $this->fillSheetTermStat();
        $this->xls->setActiveSheetIndex(0);
    }
    
    protected function convertToJsonStyleIndex(array $files, string $type) {
        foreach($files as $file) {
            $id = $file['fileId'];
            settype($this->data[$id], 'array');
            $target = &$this->data[$id];
            $target['fileId'] = $file['fileId'];
            $target['fileName'] = $file['fileName'];
            $target['segmentsPerFile'] = $file['segmentsPerFile'];
            unset($file['fileId']);
            unset($file['fileName']);
            unset($file['segmentsPerFile']);
            foreach($file as $key => $value) {
                $newKey = $type.'.'.$file['fieldName'].'.'.$key;
                if($key != 'statByState') {
                    $target[$newKey] = $value;
                    continue;
                }
                if($file['fieldName'] != self::FIELD_SOURCE) {
                    continue;
                }
                foreach($value as $state => $stats) {
                    $statKey = $newKey.'.'.$state;
                    $target[$statKey.'.foundSum'] = $stats['foundSum'];
                    $target[$statKey.'.notFoundSum'] = $stats['notFoundSum'];
                }
            }
        }
    }
    
    /**
     * Writes the Statistics in the given Format to the disk
     * Filename without suffix, suffix is appended by this method
     * @param string $filename
     */
    public function writeToDisk(string $filename) {
        $w = new PHPExcel_Writer_Excel2007($this->xls);
        $w->save($filename.self::FILE_SUFFIX);
    }
    
    protected function fillSheetOverview() {
        $this->fillByTemplate(3, 0);
    }
    
    protected function fillSheetSummary() {
        $this->fillByTemplate(3, 1);
    }
    
    /**
     * Uses a given row of a given Sheet as template and adds new rows with the defined values
     * @param integer $tplRow
     * @param integer $sheetIdx
     */
    protected function fillByTemplate($tplRow, $sheetIdx){
        $sheet = $this->xls->setActiveSheetIndex($sheetIdx);
        $maxColumn = $sheet->getHighestColumn($tplRow);
        $maxColumn++;
        $masterValues = array();
        for ($col = 'A'; $col != $maxColumn; $col++) {
            $cell = $sheet->getCell($col.$tplRow);
            $masterValues[$cell->getColumn()] = $cell->getValue();
        }
        
        $i = $tplRow + 1; // start after tpl row
        foreach($this->data as $file) {
            $sheet->insertNewRowBefore($i);
            foreach($masterValues as $col => $tpl) {
                $isTpl = (strpos($tpl, self::TPL_PREFIX) === 0);
                if(!$isTpl) {
                    $sheet->setCellValue($col.$i, $tpl);
                    continue;
                }
                $tpl = substr($tpl, strlen(self::TPL_PREFIX));
                if(isset($file[$tpl])) {
                    $sheet->setCellValue($col.$i, $file[$tpl]);
                }
                //if nothing at all leave column empty
            }
            $i++;
        }
        $sheet->removeRow($tplRow);
    }
    
    /**
     * Adds the source terms and their [not]Found counters
     */
    protected function fillSheetTermStat() {
        $termStat = ZfExtended_Factory::get('editor_Plugins_SegmentStatistics_Models_TermStatistics');
        /* @var $termStat editor_Plugins_SegmentStatistics_Models_TermStatistics */
        $stats = $termStat->loadTermSums($this->taskGuid, self::FIELD_SOURCE);
        
        $sheet = $this->xls->setActiveSheetIndex(2);
        $i = 2;
        foreach ($stats as $stat) {
            $sheet->setCellValue('A'.$i, $stat['term']);
            $sheet->setCellValue('B'.$i, $stat['foundSum']);
            $sheet->setCellValue('C'.$i++, $stat['notFoundSum']);
        }
    }
}