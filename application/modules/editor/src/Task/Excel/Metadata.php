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

namespace MittagQI\Translate5\Task\Excel;

use editor_Models_Task;
use editor_Workflow_Default;
use editor_Workflow_Exception;
use editor_Workflow_Manager;
use MittagQI\Translate5\Repository\CustomerRepository;
use MittagQI\Translate5\Repository\LanguageRepository;
use MittagQI\Translate5\Task\CustomFields\Field;
use MittagQI\ZfExtended\Models\Entity\ExcelExport;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use ReflectionException;
use ZfExtended_Authentication;
use ZfExtended_Factory as Factory;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_Zendoverwrites_Translate;

/**
 * General model for Excel Metadata (= task overview and statistics).
 * Handles all interactions with the PHPSpreadsheet (via \MittagQI\ZfExtended\Models\Entity\ExcelExport).
 */

class Metadata
{
    /**
     * general max width for all cols in the Excel.
     * @var int
     */
    public const COL_MAX_WIDTH = 40; // just test what looks best

    protected ExcelExport $excelExport;

    /**
     * The name of the sheet that contains the 'task overview' data (aka the tasks)
     */
    protected string $sheetNameTaskOverview;

    /**
     * The name of the sheet that contains the 'meta data' (aka filtering and KPI-statistics)
     */
    protected string $sheetNameMetadata;

    /**
     * the number of the row of the next task
     */
    protected int $taskRow = 2;

    /**
     * columns to show for the tasks
     */
    protected array $taskColumns = [];

    /**
     * Info about task custom columns
     */
    protected array $taskCustomColumns = [];

    /**
     * the number of the row in the metadata-sheet
     */
    protected int $metadataRow = 1;

    protected ZfExtended_Zendoverwrites_Translate $translate;

    private string $locale;

    private editor_Workflow_Manager $workflowManager;

    private LanguageRepository $languageRepository;

    private CustomerRepository $customerRepository;

    /**
     * Create a new, empty excel
     */
    public function __construct()
    {
        $this->excelExport = new ExcelExport();
        $this->excelExport->initDefaultFormat();
        $this->translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        $this->sheetNameTaskOverview = $this->translate->_('Aufgaben');
        $this->sheetNameMetadata = $this->translate->_('Meta-Daten');
        $this->locale = ZfExtended_Authentication::getInstance()->getUser()->getLocale();
        $this->workflowManager = new editor_Workflow_Manager();
        $this->languageRepository = LanguageRepository::create();
        $this->customerRepository = CustomerRepository::create();
    }

    /**
     * Init the Excel-file for our purpose.
     * @throws Exception
     * @throws ReflectionException
     */
    public function initExcel(array $columns): void
    {
        $this->taskColumns = $columns;

        // remove initial sheet
        $this->excelExport->removeWorksheetByIndex(0);

        // add two sheets 'task overview' and 'meta data'
        $this->excelExport->addWorksheet($this->sheetNameTaskOverview, 0);
        $this->excelExport->addWorksheet($this->sheetNameMetadata, 1);

        // and init the sheets taskoverview + meta
        $this->initSheetTaskOverview();
        $this->initSheetMeta();
    }

    /**
     * Init the sheet 'task overview'.
     * @throws ReflectionException
     * @throws Exception
     */
    protected function initSheetTaskOverview(): void
    {
        $sheet = $this->excelExport->getWorksheetByName($this->sheetNameTaskOverview);

        // set font-size to "12" for the whole sheet
        $sheet->getParent()->getDefaultStyle()->applyFromArray([
            'font' => [
                'size' => '12',
            ],
        ]);

        $customFields = Factory::get(Field::class)->loadAllSorted();

        foreach ($customFields as $customField) {
            // Get label according to current locale
            $label = json_decode($customField['label'], true)[$this->locale];

            $index = "customField{$customField['id']}";

            $this->taskCustomColumns[$index]['header'] = $label;

            if ($customField['type'] === 'checkbox') {
                $this->taskCustomColumns[$index]['value'] = [
                    0 => 'No',
                    1 => 'Yes',
                ];
            } elseif ($customField['type'] === 'combobox') {
                foreach (json_decode($customField['comboboxData'], true) as $value => $l10nTitle) {
                    $this->taskCustomColumns[$index]['value'][$value] = $l10nTitle;
                }
            }
        }

        // write fieldnames in header, set their font to bold, set their width to auto
        $sheetCol = 'A';
        foreach ($this->taskColumns as $colName) {
            // Not all column-names in the taskGrid have a translation.
            if (array_key_exists($colName, editor_Models_Task::TASKGRID_TEXTCOLS)) {
                $colHeadline = $this->translate->_(editor_Models_Task::TASKGRID_TEXTCOLS[$colName]);
            } elseif (array_key_exists($colName, $this->taskCustomColumns)) {
                $colHeadline = $this->taskCustomColumns[$colName]['header'];
            } else {
                $colHeadline = $colName;
            }
            $sheet->setCellValue($sheetCol . '1', ucfirst($colHeadline));
            $sheet->getStyle($sheetCol . '1')->getFont()->setBold(true);
            $sheet->getColumnDimension($sheetCol)->setAutoSize(true);
            $sheetCol++; //inc alphabetical
        }
    }

    /**
     * init the sheet 'meta data'
     * @throws Exception
     */
    protected function initSheetMeta(): void
    {
        $sheet = $this->excelExport->getWorksheetByName($this->sheetNameMetadata);

        // set font-size to "12" for the whole sheet
        $sheet->getParent()->getDefaultStyle()->applyFromArray([
            'font' => [
                'size' => '12',
            ],
        ]);

        // set column width
        $sheet->getColumnDimension('A')->setWidth(200);
    }

    /**
     * Add a task to the Excel. The result should look exactly as in the taskGrid.
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws editor_Workflow_Exception
     */
    public function addTask(array $task): void
    {
        $sheet = $this->excelExport->getWorksheetByName($this->sheetNameTaskOverview);
        $sheetCol = 'A';
        foreach ($this->taskColumns as $colName) {
            if (! array_key_exists($colName, $task)) {
                // eg taskassoc is not always set for every task
                $sheetCol++;

                continue;
            }
            switch ($colName) {
                case 'customerId':
                    $value = $this->customerRepository->get($task['customerId'] ?? 0)->getName();

                    break;
                case 'orderdate':
                case 'enddate':
                    $value = Date::stringToExcel($task[$colName] ?? '');
                    if ($value === false) {
                        $value = null;
                    } else {
                        $sheet->getStyle($sheetCol . $this->taskRow)
                            ->getNumberFormat()
                            ->setFormatCode(
                                NumberFormat::FORMAT_DATE_YYYYMMDD
                            );
                    }

                    break;
                case 'relaisLang':
                case 'sourceLang':
                case 'targetLang':
                    $value = $this->getLanguage((int) ($task[$colName] ?? 0));

                    break;
                case 'state':
                    $value = $this->getTaskState($task);

                    break;
                case 'workflow':
                    $value = $task['workflow'] . ' (' . $task['workflowStepName'] . ')';

                    break;
                case 'taskassocs':
                    $value = $this->getTaskAssocs($task['taskassocs']);

                    break;
                default:
                    /* customField1 value example:
                    Array ( [en] => first value dropdown
                            [de] => erster Wert Dropdown ) */
                    $value = $this->taskCustomColumns[$colName]['value'][$task[$colName]][$this->locale]
                        ?? $task[$colName];

                    break;
            }

            $sheet->setCellValue($sheetCol . $this->taskRow, $value);
            $sheetCol++;
        }
        $this->taskRow++;
    }

    /**
     * Add a headline to the metadata-sheet.
     */
    public function addMetadataHeadline($headline): void
    {
        $sheet = $this->excelExport->getWorksheetByName($this->sheetNameMetadata);
        $sheet->setCellValue('A' . $this->metadataRow, $headline);
        $sheet->getStyle('A' . $this->metadataRow)->getFont()->setBold(true);
        $this->metadataRow++;
    }

    /**
     * Add filter-setting to the Excel.
     */
    public function addFilter(string $property, string $operator, string $value): void
    {
        $sheet = $this->excelExport->getWorksheetByName($this->sheetNameMetadata);
        $sheet->setCellValue('A' . $this->metadataRow, $property . ' ' . $operator . ' ' . $value);
        $this->metadataRow++;
    }

    /**
     * Add a KPI-item to the Excel.
     */
    public function addKPI(string $kpiValue): void
    {
        $sheet = $this->excelExport->getWorksheetByName($this->sheetNameMetadata);
        $sheet->setCellValue('A' . $this->metadataRow, $kpiValue);
        $this->metadataRow++;
    }

    /**
     * Get the excel as Spreadsheet object
     */
    public function getSpreadsheet(): Spreadsheet
    {
        return $this->excelExport->getSpreadsheet();
    }

    /**
     * Set autowidth with maximum for all columns in the Excel.
     */
    public function setColWidth(): void
    {
        // https://github.com/PHPOffice/PhpSpreadsheet/issues/275
        foreach ($this->excelExport->getAllWorksheets() as $sheet) {
            $sheet->calculateColumnWidths();
            foreach ($sheet->getColumnDimensions() as $colDim) {
                if (! $colDim->getAutoSize()) {
                    continue;
                }
                $colWidth = $colDim->getWidth();
                if ($colWidth > self::COL_MAX_WIDTH) {
                    $colDim->setAutoSize(false);
                    $colDim->setWidth(self::COL_MAX_WIDTH);
                }
            }
        }
    }

    private function getTaskAssocs($taskassocs): string
    {
        $allTaskassocs = $taskassocs;
        $values = [];
        foreach ($allTaskassocs as $assoc) {
            $values[] = $assoc['name'] . ' (' . $assoc['serviceName'] . ')';
        }

        return count($allTaskassocs) . ': ' . implode(', ', $values);
    }

    /**
     * @throws editor_Workflow_Exception
     */
    private function getTaskState(array $task): string
    {
        try {
            $workflow = $this->workflowManager->getActive($task['taskGuid']);
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            $workflow = $this->workflowManager->get('default');
        }
        /* @var $workflow editor_Workflow_Default */
        $states = $workflow->getStates();
        $labels = $workflow->getLabels();

        return (in_array($task['state'], $states)) ? $labels[array_search(
            $task['state'],
            $states
        )] : $task['state'];
    }

    private function getLanguage(int $languageId): string
    {
        if ($languageId === 0) {
            // relaisLang might not be set = ok
            $value = '';
        } else {
            try {
                $language = $this->languageRepository->get($languageId);
                $value = $language->getLangName() . ' (' . $language->getRfc5646() . ')';
            } catch (ZfExtended_Models_Entity_NotFoundException) {
                $value = '- notfound -';
            }
        }

        return $value;
    }
}
