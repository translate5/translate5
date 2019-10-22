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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *

 /**
 * General model for Excel Metadata (= task overview and statistics).
 */

class editor_Models_Task_Excel_Metadata extends editor_Models_PHPSpreadsheet {
    
    /**
     * The name of the sheet that contains the 'task overview' data (aka the tasks)
     * @var string
     */
    protected static $sheetNameTaskOverview = 'task overview';
    /**
     * The name of the sheet that contains the 'meta data' (aka filtering and KPI-statistics)
     * @var string
     */
    protected static $sheetNameMeta = 'meta data';
    
    /**
     * the number of the row of the next task:
     * - will be written by ->addTask()
     * - or will be read out by ->getTask()
     * @var integer
     */
    protected $taskRow = 2;
    
    /**
     * Init the Excel-file for our purpose.
     */
    public function initExcel() {
        // add two sheets 'task overview' and 'meta data'
        $this->addSheet(self::$sheetNameTaskOverview, 0);
        $this->addSheet(self::$sheetNameMeta, 1);
        
        // and init the sheets taskoverview + meta
        $this->initSheetTaskOverview();
        $this->initSheetMeta();
    }
    
    /**
     * init the sheet 'review job'
     */
    protected function initSheetTaskOverview() {
    }
    
    /**
     * init the sheet meta data'
     */
    protected function initSheetMeta() {
    }
}

/**
 * Helper class to define a structure for the task data stored in the excel 
 */
class taskExcelMetadataTaskContainer {
    public $nr;
    public $source;
    public $target;
    public $comment;
}