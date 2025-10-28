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

namespace MittagQI\Translate5\Task\Export;

use DateTime;
use editor_Models_KPI;
use editor_Models_Workflow_Step;
use editor_Workflow_Exception;
use Exception;
use MittagQI\Translate5\Task\Excel\Metadata as ExcelMetadata;
use MittagQI\Translate5\Task\Excel\MetadataException;
use MittagQI\ZfExtended\Controller\Response\Header;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use ReflectionException;
use Zend_Exception;
use Zend_Registry;
use ZfExtended_Factory;
use ZfExtended_Logger;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_Models_Filter_ExtJs6;
use ZfExtended_Models_User;
use ZfExtended_Zendoverwrites_Translate;

/**
 * Export given tasks, their filtering and their key performance indicators (KPI) as an Excel-file.
 * This class should not directly interact with the PHPSpreadsheet, this is done via editor_Models_Task_Excel_Metadata.
 * TODO: Achieve this completely by refactoring export(), exportAsDownload() and exportAsFile().
 */
class Metadata
{
    protected ExcelMetadata $excelMetadata;

    /**
     * Tasks as currently filtered by the user.
     */
    protected array $tasks;

    /**
     * Filters currently applied by the user.
     */
    protected array $filters;

    /**
     * Visible columns of the task-grid (order and names).
     */
    protected array $columns;

    /**
     * Key Performance Indicators (KPI) for the current tasks.
     */
    protected array $kpiStatistics;

    protected ZfExtended_Zendoverwrites_Translate $translate;

    protected ZfExtended_Logger $log;

    /***
     * Kpi locale string
     * @var array
     */
    protected array $kpiTypeLocales = [];

    /**
     * @throws Zend_Exception
     */
    public function __construct()
    {
        $this->translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        $this->log = Zend_Registry::get('logger')->cloneMe('editor.task.excel.metadata');

        $this->kpiTypeLocales['processingTime'] = $this->translate->_('Ø Bearbeitungszeit') . ' / ';
        $this->kpiTypeLocales['workflowStep'] = $this->translate->_('Workflow Schritt');
        $type = $this->translate->_('Typ');
        foreach ([
            editor_Models_KPI::KPI_REVIEWER => 'Lektorat',
            editor_Models_KPI::KPI_TRANSLATOR => 'Übersetzung',
            editor_Models_KPI::KPI_TRANSLATOR_CHECK => 'Finales Lektorat',
        ] as $key => $workflowStepTypeName) {
            $this->kpiTypeLocales[$key] = $type . ' ' . $this->translate->_($workflowStepTypeName);
        }

        foreach ([
            editor_Models_KPI::KPI_LEVENSHTEIN_START => 'Ø Levenshtein-Distanz vor Beginn des Workflows',
            editor_Models_KPI::KPI_DURATION_START => 'Ø Nachbearbeitungszeit vor Beginn des Workflows',
            editor_Models_KPI::KPI_DURATION => 'Ø Nachbearbeitungszeit innerhalb eines Workflowschritts',
            editor_Models_KPI::KPI_LEVENSHTEIN_PREVIOUS => 'Ø Levenshtein-Abstand innerhalb eines Workflowschritts',
            editor_Models_KPI::KPI_DURATION_TOTAL => 'Ø Nachbearbeitungszeit ab Beginn des Workflows',
            editor_Models_KPI::KPI_LEVENSHTEIN_ORIGINAL => 'Ø Levenshtein-Abstand ab Beginn des Workflows',
            editor_Models_KPI::KPI_LEVENSHTEIN_END => 'Ø Levenshtein-Distanz nach Ende des Workflows',
            editor_Models_KPI::KPI_DURATION_END => 'Ø Nachbearbeitungszeit nach Ende des Workflows',
        ] as $key => $text) {
            $this->kpiTypeLocales[$key] = $this->translate->_($text);
        }
    }

    /**
     * Set tasks.
     */
    public function setTasks(array $rows): void
    {
        $this->tasks = $rows;
    }

    /**
     * Set the filters that the user applied in the task overview.
     */
    public function setFilters(array $filters): void
    {
        $this->filters = $filters;
    }

    /**
     * Set the columns that are currently visible in the task overview.
     */
    public function setColumns(array $columns): void
    {
        $this->columns = $columns;
    }

    /**
     * Set KPI-statistics.
     */
    public function setKpiStatistics(array $kpiStatistics): void
    {
        $this->kpiStatistics = $kpiStatistics;
    }

    /**
     * Get a KPI-value by the indicator's name.
     */
    protected function getKpiValueByName(string $name): string
    {
        return $this->kpiStatistics[$name];
    }

    /**
     * provides the excel as download to the browser
     * @throws MetadataException
     */
    public function exportAsDownload(): void
    {
        try {
            $this->export('php://output');
        } catch (Exception $e) {
            throw new MetadataException('E1170', previous: $e);
        }
        Header::sendDownload(
            $this->getFilenameForDownload(),
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'max-age=0'
        );
        exit;
    }

    /**
     * does the export
     * @param string $fileName where the XLS should go to
     * @throws ReflectionException
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws editor_Workflow_Exception
     * @throws Exception
     */
    protected function export(string $fileName): void
    {
        $this->excelMetadata = ZfExtended_Factory::get(ExcelMetadata::class);
        $this->excelMetadata->initExcel($this->columns);

        // add data: filters
        $this->excelMetadata->addMetadataHeadline($this->translate->_('Filter'));

        //validate if filter value is date
        $isDate = function ($value) {
            if (! $value || is_array($value) || is_object($value)) {
                return false;
            }

            try {
                (new DateTime($value));

                return true;
            } catch (Exception) {
                return false;
            }
        };

        //convert the userName filter value(the initial value is guid)
        $convertUserName = function ($filter) {
            if (empty($filter->value)) {
                return;
            }

            $model = ZfExtended_Factory::get('ZfExtended_Models_User');
            /* @var $model ZfExtended_Models_User */

            foreach ($filter->value as &$single) {
                try {
                    $model->loadByGuid($single);
                    $single = $model->getUserName();
                } catch (Exception) {
                    //catch notfound, this should not happen
                }
            }
        };
        $filter = ZfExtended_Factory::get(ZfExtended_Models_Filter_ExtJs6::class);
        $operatorTranslated = $filter->getTranslatedOperators();
        $workflowStepTypes = null;
        foreach ($this->filters as $filter) {
            //translate the filter operators
            if (isset($operatorTranslated[$filter->operator])) {
                $filter->operator = $operatorTranslated[$filter->operator];
            }

            if ($filter->property == 'userName') {
                $convertUserName($filter);
            } elseif ($filter->property == 'workflowUserRole') {
                $workflowStepTypes = $filter->value;
            } elseif ($isDate($filter->value)) {
                $date = new DateTime($filter->value);
                $filter->value = $date->format('Y-m-d');
            }

            $this->excelMetadata->addFilter(
                $filter->property,
                $filter->operator,
                is_array($filter->value) ? implode(', ', $filter->value) : $filter->value,
            );
        }

        //add empty filter entry
        if (count($this->filters) === 0) {
            $this->excelMetadata->addFilter(
                '',
                '',
                '-',
            );
        }

        // add data: KPI
        $this->excelMetadata->addMetadataHeadline($this->translate->_('KPI'));

        if (isset($this->kpiStatistics['byWorkflowSteps'])) {
            $workflowSteps = explode(',', $this->kpiStatistics['byWorkflowSteps']);
            $stepModel = new editor_Models_Workflow_Step();
            $stepLabels = $stepModel->getAllLabels();
            foreach ($workflowSteps as $workflowStep) {
                $this->excelMetadata->addKPI(
                    $this->kpiTypeLocales['processingTime'] . $this->kpiTypeLocales['workflowStep'] . ' ' .
                    $this->translate->_($stepLabels[$workflowStep]) . ': ' . $this->kpiStatistics[$workflowStep]
                );
            }
        } else {
            foreach (editor_Models_KPI::ROLE_TO_KPI_KEY as $role => $key) {
                if ($workflowStepTypes === null || in_array($role, $workflowStepTypes)) {
                    $this->excelMetadata->addKPI(
                        $this->kpiTypeLocales['processingTime'] . ($this->kpiTypeLocales[$key] ?? '') . ': ' .
                        $this->kpiStatistics[$key]
                    );
                }
            }
        }

        foreach (editor_Models_KPI::getAggregateMetrics() as $key) {
            $this->excelMetadata->addKPI($this->kpiTypeLocales[$key] . ': ' . $this->kpiStatistics[$key]);
        }

        $this->excelMetadata->addKPI($this->kpiStatistics['excelExportUsage']
            . ' ' . $this->translate->_('Excel-Export Nutzung'));

        // add data: tasks
        foreach ($this->tasks as $task) {
            $this->excelMetadata->addTask($task);
        }
        // what we added latest, will be the first sheet when opening the excel-file.

        // finalize the layout
        $this->excelMetadata->setColWidth();

        // .. then send the excel
        $writer = new Xlsx($this->excelMetadata->getSpreadsheet());
        $writer->save($fileName);
    }

    protected function getFilenameForDownload(): string
    {
        return 'metadataExport_' . date("Y-m-d h:i:sa") . '.xlsx';
    }
}
