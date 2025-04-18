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

use MittagQI\ZfExtended\Controller\Response\Header;

/**
 * Export given tasks, their filtering and their key performance indicators (KPI) as an Excel-file.
 * This class should not directly interact with the PHPSpreadsheet, this is done via editor_Models_Task_Excel_Metadata.
 * TODO: Achieve this completely by refactoring export(), exportAsDownload() and exportAsFile().
 */
class editor_Models_Task_Export_Metadata
{
    /**
     * @var editor_Models_Task_Excel_Metadata
     */
    protected $excelMetadata;

    /**
     * Tasks as currently filtered by the user.
     * @var array
     */
    protected $tasks;

    /**
     * Filters currently applied by the user.
     * @var array
     */
    protected $filters;

    /**
     * Visible columns of the task-grid (order and names).
     * @var array
     */
    protected $columns;

    /**
     * Key Performance Indicators (KPI) for the current tasks.
     * @var array
     */
    protected $kpiStatistics;

    /**
     * @var ZfExtended_Zendoverwrites_Translate
     */
    protected $translate;

    /**
     * @var ZfExtended_Logger
     */
    protected $log;

    /***
     * Kpi locale string
     * @var array
     */
    protected $kpiTypeLocales = [];

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

        $this->kpiTypeLocales[editor_Models_KPI::KPI_LEVENSHTEIN_START] = $this->translate->_('Ø Levenshtein-Distanz vor Beginn des Workflows');
        $this->kpiTypeLocales[editor_Models_KPI::KPI_DURATION_START] = $this->translate->_('Ø Nachbearbeitungszeit vor Beginn des Workflows');
        $this->kpiTypeLocales[editor_Models_KPI::KPI_DURATION] = $this->translate->_('Ø Nachbearbeitungszeit innerhalb eines Workflowschritts');
        $this->kpiTypeLocales[editor_Models_KPI::KPI_LEVENSHTEIN_PREVIOUS] = $this->translate->_('Ø Levenshtein-Abstand innerhalb eines Workflowschritts');
        $this->kpiTypeLocales[editor_Models_KPI::KPI_DURATION_TOTAL] = $this->translate->_('Ø Nachbearbeitungszeit ab Beginn des Workflows');
        $this->kpiTypeLocales[editor_Models_KPI::KPI_LEVENSHTEIN_ORIGINAL] = $this->translate->_('Ø Levenshtein-Abstand ab Beginn des Workflows');
        $this->kpiTypeLocales[editor_Models_KPI::KPI_LEVENSHTEIN_END] = $this->translate->_('Ø Levenshtein-Distanz nach Ende des Workflows');
        $this->kpiTypeLocales[editor_Models_KPI::KPI_DURATION_END] = $this->translate->_('Ø Nachbearbeitungszeit nach Ende des Workflows');
    }

    /**
     * Set tasks.
     */
    public function setTasks(array $rows)
    {
        $this->tasks = $rows;
    }

    /**
     * Set the filters that the user applied in the task overview.
     */
    public function setFilters(array $filters)
    {
        $this->filters = $filters;
    }

    /**
     * Set the columns that are currently visible in the task overview.
     */
    public function setColumns(array $columns)
    {
        $this->columns = $columns;
    }

    /**
     * Set KPI-statistics.
     */
    public function setKpiStatistics(array $kpiStatistics)
    {
        $this->kpiStatistics = $kpiStatistics;
    }

    /**
     * Get a KPI-value by the indicator's name.
     * @return string
     */
    protected function getKpiValueByName(string $name)
    {
        return $this->kpiStatistics[$name];
    }

    /**
     * provides the excel as download to the browser
     */
    public function exportAsDownload(): void
    {
        try {
            $this->export('php://output');
        } catch (Exception $e) {
            throw new editor_Models_Task_Excel_MetadataException('E1170', [], $e);
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
     */
    protected function export(string $fileName): void
    {
        $this->excelMetadata = ZfExtended_Factory::get('editor_Models_Task_Excel_Metadata');
        $this->excelMetadata->initExcel($this->columns);

        // add data: filters
        $this->excelMetadata->addMetadataHeadline($this->translate->_('Filter'));
        if (count($this->filters) == 0) {
            $this->filters[] = (object) [
                'property' => ' ',
                'operator' => ' ',
                'value' => '-',
            ];
        }

        //validate if filter value is date
        $isDate = function ($value) {
            if (! $value || is_array($value) || is_object($value)) {
                return false;
            }

            try {
                new DateTime($value);

                return true;
            } catch (Exception $e) {
                return false;
            }
        };

        //convert the userName filter value(the initial value is guid)
        $convertUserName = function ($filter) {
            if (! isset($filter->value) || empty($filter->value)) {
                return false;
            }

            $model = ZfExtended_Factory::get('ZfExtended_Models_User');
            /* @var $model ZfExtended_Models_User */

            foreach ($filter->value as &$single) {
                try {
                    $model->loadByGuid($single);
                    $single = $model->getUserName();
                } catch (Exception $e) {
                    //catch notfound, this should not happen
                }
            }
        };
        $filter = ZfExtended_Factory::get('ZfExtended_Models_Filter_ExtJs6');
        /* @var $filter ZfExtended_Models_Filter_ExtJs6 */
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

            $this->excelMetadata->addFilter($filter);
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

        $this->excelMetadata->addKPI($this->kpiStatistics['excelExportUsage'] . ' ' . $this->translate->_('Excel-Export Nutzung'));

        // add data: tasks
        foreach ($this->tasks as $task) {
            $this->excelMetadata->addTask($task);
        }
        // what we added latest, will be the first sheet when opening the excel-file.

        // finalize the layout
        $this->excelMetadata->setColWidth();

        // .. then send the excel
        $writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx($this->excelMetadata->getSpreadsheet());
        $writer->save($fileName);
    }

    /**
     * @return string
     */
    protected function getFilenameForDownload()
    {
        return 'metadataExport_' . date("Y-m-d h:i:sa") . '.xlsx';
    }
}
