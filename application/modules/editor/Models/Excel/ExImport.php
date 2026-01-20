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

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Protection;

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *

 /**
 * General model for Excel ex- and im-ports.
 * Will be used by the models under ../Export/Excel.php respectively ../Import/Excel.php
 * TODO: refactor (= implement with \MittagQI\ZfExtended\Models\Entity\ExcelExport)
 */
class editor_Models_Excel_ExImport
{
    private const LOCKED_CAPTION = 'Locked';

    /**
     * the taskGuid the excel belongs to
     * @var string
     */
    protected $taskGuid = null;

    /**
     * the taskName the excel belongs to
     * @var string
     */
    protected $taskName = null;

    /**
     * the mail adress of the pm of the task this excel belongs to
     * @var string
     */
    protected $taskMailPm = null;

    /**
     * Container to hold the excel-object aka PhpOffice\PhpSpreadsheet\Spreadsheet
     * @var Spreadsheet
     */
    protected $excel = null;

    /**
     * The name of the sheet that contains the 'review job' data (aka the segments)
     * @var string
     */
    protected static $sheetNameJob = 'review job';

    /**
     * The name of the sheet that contains the 'meta data' (aka pmMail, taskGuid, -Name)
     * @var string
     */
    protected static $sheetNameMeta = 'meta data';

    /**
     * the number of the row the next segment:
     * - will be written by ->addSegment()
     * - or will be read out by ->getSegments()
     * @var integer
     */
    protected $segmentRow = 2;

    /**
     * @var ZfExtended_Logger
     */
    protected $log;

    private bool $hasResourceNames = false;

    /**
     * Class should not be used with new editor_Models_Excel_ExImport()
     * You should use
     * ::createNewExcel($task) or
     * ::loadFromExcel($file)
     * to get an instance of the class
     */
    protected function __construct()
    {
        $this->log = Zend_Registry::get('logger')->cloneMe('editor.task.exceleximport');
    }

    /**
     * Create a new, empty excel
     */
    public static function createNewExcel(editor_Models_Task $task): editor_Models_Excel_ExImport
    {
        $tempExImExcel = ZfExtended_Factory::get('editor_Models_Excel_ExImport', [], false);
        /* @var editor_Models_Excel_ExImport $tempExImExcel */
        $tempExImExcel->__construct();

        // init class properties
        $tempExImExcel->taskGuid = $task->getTaskGuid();
        $tempExImExcel->taskName = $task->getTaskName();
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        $user->loadByGuid($task->getPmGuid());
        $tempExImExcel->taskMailPm = $user->getEmail();

        // create a new spreadsheet object
        $tempExImExcel->excel = new Spreadsheet();
        $stringBinder = new StringValueBinder();
        //prevent formulas from being used
        $stringBinder->setFormulaConversion(true);

        $tempExImExcel->excel->setValueBinder($stringBinder);
        $tempExImExcel->initDefaultFormat();

        // add two sheets 'review job' and 'meta data'
        $tempExImExcel->excel->removeSheetByIndex(0); // remove initial sheet
        $tempExImExcel->addSheet(self::$sheetNameJob, 0); // add sheet 'review job'
        $tempExImExcel->addSheet(self::$sheetNameMeta, 1); // add sheet 'meta data'

        // and init the sheets job + meta
        $tempExImExcel->initSheetJob();
        $tempExImExcel->initSheetMeta();

        // return the editor_Models_Excel_ExImport object
        return $tempExImExcel;
    }

    /**
     * Write the segment informations into the 'review job' sheet
     * the row this informations are written is $this->segmentRow
     */
    public function addSegment(
        int $nr,
        string $source,
        string $target,
        bool $isEditable,
        string $comments,
        ?string $resourceName
    ): void {
        $sheet = $this->getSheetJob();
        $sheet->setCellValue('A' . $this->segmentRow, $nr);
        // for the following fields setCellValueExplicit() is used. Else this fields will be interpreted as formula fields if a segment starts with "="
        $sheet->setCellValueExplicit('B' . $this->segmentRow, $isEditable ? 'No' : 'Yes', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('C' . $this->segmentRow, $source, DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('D' . $this->segmentRow, $target, DataType::TYPE_STRING);

        // disable write protection for fields 'target' and 'comment'
        if ($isEditable) {
            $sheet->getStyle('D' . $this->segmentRow)->getProtection()->setLocked(Protection::PROTECTION_UNPROTECTED);
            $sheet->getStyle('E' . $this->segmentRow)->getProtection()->setLocked(Protection::PROTECTION_UNPROTECTED);
        }

        $sheet->setCellValueExplicit('F' . $this->segmentRow, $comments, DataType::TYPE_STRING);

        if (strlen($resourceName ?? '') > 0) {
            $this->hasResourceNames = true;
            $sheet->setCellValueExplicit('G' . $this->segmentRow, $resourceName, DataType::TYPE_STRING);
        }

        $this->segmentRow++;
    }

    /**
     * Get the excel as Spreadsheet object
     */
    public function getExcel(): Spreadsheet
    {
        // set sheet 'review job' as active sheet
        $sheet = $this->getSheetJob();

        //format Resource Name (resname / segmentDescriptor) only when given
        if ($this->hasResourceNames) {
            $sheet->getColumnDimension('G')->setWidth(50);
            $sheet->setCellValue('G1', 'Resource Name');
        }

        return $this->excel;
    }

    /**
     * Load an excel from a file
     * @TODO
     */
    public static function loadFromExcel(string $filename): editor_Models_Excel_ExImport
    {
        $tempExImExcel = ZfExtended_Factory::get('editor_Models_Excel_ExImport', [], false);
        /* @var editor_Models_Excel_ExImport $tempExImExcel */
        $tempExImExcel->__construct();

        // load excel
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $tempExImExcel->excel = $reader->load($filename);

        // load relevant (meta-)informations (see also ->initSheetMeta())
        $sheet = $tempExImExcel->getSheetMeta();
        $tempExImExcel->taskGuid = $sheet->getCell('B6')->getValue();
        $tempExImExcel->taskName = $sheet->getCell('B5')->getValue();
        $tempExImExcel->taskMailPm = $sheet->getCell('B1')->getValue();

        // return the editor_Models_Excel_ExImport object
        return $tempExImExcel;
    }

    /**
     * get the taskGuid as stored in the excel
     */
    public function getTaskGuid(): string
    {
        return $this->taskGuid;
    }

    /**
     * get the taskName as stored in the excel
     */
    public function getTaskName(): string
    {
        return $this->taskName;
    }

    /**
     * get the mail of the task PM as stored in the excel
     */
    public function getTaskMailPm(): string
    {
        return $this->taskMailPm;
    }

    /**
     * Read all segments in the excel and return the informations as a list of
     * excelExImportSegmentContainer objects
     *
     * @return [excelExImportSegmentContainer]
     */
    public function getSegments(): array
    {
        // reset $this->segmentRow
        $this->segmentRow = 2;
        $tempReturn = [];
        $sheet = $this->getSheetJob();

        $colSource = 'C';
        $colTarget = 'D';
        $colComment = 'E';
        //legacy table layout (no locked column)
        $secondColumnHeadline = (string) $sheet->getCell('B1')->getValue();
        if ($secondColumnHeadline !== self::LOCKED_CAPTION) {
            $colSource = 'B';
            $colTarget = 'C';
            $colComment = 'D';
        }

        // read the excel rows until the field which contains the task-nr is empty
        while ($nr = $sheet->getCell('A' . $this->segmentRow)->getValue()) {
            if ($colSource === 'C' && (string) $sheet->getCell('B' . $this->segmentRow)->getValue() === 'Yes') {
                $this->segmentRow++;
                $tempReturn[] = null; //null → segment to be ignored on re-import

                continue;
            }

            $tempSegment = new excelExImportSegmentContainer();
            $tempSegment->nr = $nr;
            $tempSegment->source = $sheet->getCell($colSource . $this->segmentRow)->getValue();
            $tempSegment->target = $sheet->getCell($colTarget . $this->segmentRow)->getValue();
            $tempSegment->comment = trim($sheet->getCell($colComment . $this->segmentRow)->getValue() ?: '');
            $tempReturn[] = $tempSegment;

            $this->segmentRow++;
        }

        return $tempReturn;
    }

    /**
     * set global document format settings
     */
    protected function initDefaultFormat()
    {
        $this->excel->getDefaultStyle()->getAlignment()
            // vertical align: top;
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP)
            // text-align: left;
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT)
            // auto-wrap text to new line;
            ->setWrapText(true);
        // @TODO: add some padding to all fields... but how??
    }

    /**
     * Add a new sheet to the excel
     */
    protected function addSheet(string $name, int $index): void
    {
        $tempSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($this->excel, $name);
        $tempSheet->getDefaultColumnDimension()->setAutoSize(true); // does not work propper in Libre-Office. With Microsoft-Office everything is OK.
        $this->excel->addSheet($tempSheet, $index);
    }

    /**
     * Returns the worksheet which contains the job data (the segments)
     * @return PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
     */
    protected function getSheetJob()
    {
        $this->excel->setActiveSheetIndexByName(self::$sheetNameJob);

        return $this->excel->getActiveSheet();
    }

    /**
     * Returns the worksheet which contains the meta data (taskGuid etc.)
     * @return PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
     */
    protected function getSheetMeta()
    {
        $this->excel->setActiveSheetIndexByName(self::$sheetNameMeta);

        return $this->excel->getActiveSheet();
    }

    /**
     * init the sheet 'review job'
     */
    protected function initSheetJob()
    {
        $sheet = $this->getSheetJob();
        // setting write protection for the whole sheet
        $sheet->getProtection()->setSheet(true);

        // allow column/row resizing
        $sheet->getProtection()->setFormatColumns(false);
        $sheet->getProtection()->setFormatRows(false);

        // set font-size to "14" for the whole sheet
        $sheet->getParent()->getDefaultStyle()->applyFromArray([
            'font' => [
                'size' => '14',
            ],
        ]);

        // set column width
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(8);
        $sheet->getColumnDimension('C')->setWidth(70);
        $sheet->getColumnDimension('D')->setWidth(70);
        $sheet->getColumnDimension('E')->setWidth(50);
        $sheet->getColumnDimension('F')->setWidth(50);

        // write fieldnames in header
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', self::LOCKED_CAPTION);
        $sheet->setCellValue('C1', 'Source');
        $sheet->setCellValue('D1', 'Target (Please enter your changes in this column)');
        $sheet->setCellValue('E1', 'New Comment');
        $sheet->setCellValue('F1', 'Comments from translate5');
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
    }

    /**
     * init the sheet meta data'
     */
    protected function initSheetMeta()
    {
        $sheet = $this->getSheetMeta();
        // setting write protection for the whole sheet
        $sheet->getProtection()->setSheet(true);

        // set column width
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(70);

        // write all needed informations
        $sheet->setCellValue('A1', 'E-Mail:');
        $sheet->setCellValue('B1', $this->taskMailPm);
        $sheet->setCellValue('A2', 'Please send Excel file back to the above mail address');
        $sheet->getStyle('A1:B2')->getFont()->setBold(true);
        $sheet->getStyle('A2')->getAlignment()->setWrapText(false);

        $sheet->setCellValue('A5', 'Task-Name:');
        $sheet->setCellValue('B5', $this->taskName);
        $sheet->setCellValue('A6', 'Task-Guid:');
        $sheet->setCellValue('B6', $this->taskGuid);

        //deactivate writing for CELL B6 (taskGuid). Only enabled for development
        //$sheet->getStyle('B6')->getProtection()->setLocked(\PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_UNPROTECTED);
    }
}

/**
 * Helper class to define a structure for the segment data stored in the excel
 */
class excelExImportSegmentContainer
{
    public $nr;

    public $source;

    public $target;

    public $comment;
}
