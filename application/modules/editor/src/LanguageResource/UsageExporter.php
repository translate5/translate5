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
declare(strict_types=1);

namespace MittagQI\Translate5\LanguageResource;

use DirectoryIterator;
use editor_Models_Customer_Customer;
use editor_Models_LanguageResources_UsageLogger;
use editor_Models_LanguageResources_UsageSumLogger;
use editor_Models_Languages;
use editor_Models_TaskUsageLog;
use editor_Task_Type;
use FilesystemIterator;
use MittagQI\Translate5\LanguageResource\UsageExporter\Excel;
use MittagQI\Translate5\LanguageResource\UsageExporter\Exception;
use MittagQI\ZfExtended\Controller\Response\Header;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionException;
use ZfExtended_Factory;
use ZfExtended_Models_User;
use ZfExtended_Zendoverwrites_Translate;
use ZipArchive;

class UsageExporter
{
    private const CHUNKS_BUFFER = 1000000; //number of log entries per excel file

    #[\MittagQI\ZfExtended\Localization\LocalizableArrayProp]
    protected array $labels = [
        "langageResourceType" => "Typ der Ressource",
        "langageResourceName" => "Name der Ressource",
        "langageResourceServiceName" => "Ressource",
        "customerId" => "Kunde",
        "sourceLang" => "Quellsprache",
        "targetLang" => "Zielsprache",
        "yearAndMonth" => "Jahr/Monat",
        "totalCharacters" => "Übersetzte Zeichen",
        "timestamp" => "Zeitstempel",
        "charactersPerCustomer" => "Übersetzte Zeichen",
        "taskCount" => "Anzahl der mit InstantTranslate übersetzten Dokumente",
        "customers" => "Kunden",
        "repetition" => "Wiederholung",
        "userGuid" => "Benutzer",
    ];

    protected string $spreadSheetName;

    protected string $zipFolder;

    protected string $zipName;

    /**
     * Xls folder location
     */
    protected string $xlsFolder;

    /**
     * Xls file name
     */
    protected string $xlsName;

    protected int $worksheetIndex = 0;

    /**
     * @var array[]
     */
    private array $languageForNormalization;

    /**
     * @var array[]
     */
    private array $usersForNormalization;

    /**
     * @var array[]
     */
    private array $customersForNormalization;

    private Excel $excel;

    /**
     * @throws Exception
     */
    public static function create(): self
    {
        return new self(
            editor_Task_Type::getInstance()->getUsageExportTypes(),
            ZfExtended_Zendoverwrites_Translate::getInstance(),
            new editor_Models_Languages(),
            new editor_Models_Customer_Customer(),
            new ZfExtended_Models_User(),
            new editor_Models_LanguageResources_UsageSumLogger(),
            new editor_Models_LanguageResources_UsageLogger(),
            new editor_Models_TaskUsageLog(),
        );
    }

    /**
     * @throws Exception
     */
    public function __construct(
        private array $documentTaskType,
        private readonly ZfExtended_Zendoverwrites_Translate $translate,
        private readonly editor_Models_Languages $languageModel,
        private readonly editor_Models_Customer_Customer $customerModel,
        private readonly ZfExtended_Models_User $userModel,
        private readonly editor_Models_LanguageResources_UsageSumLogger $usageSumLogger,
        private readonly editor_Models_LanguageResources_UsageLogger $usageLogger,
        private readonly editor_Models_TaskUsageLog $taskUsageLog,
    ) {
        foreach ($this->labels as $label => $text) {
            $this->labels[$label] = $this->translate->_($text);
        }
        $this->initExcelExport();

        $this->languageForNormalization = $this->languageModel->loadAllKeyCustom('id');
        $this->usersForNormalization = $this->userModel->loadAllKeyCustom('userGuid');
        $this->customersForNormalization = $this->customerModel->loadAllKeyCustom('id');
    }

    /**
     * Setup the excel export object, cell value callbacks and labels
     * @throws Exception
     */
    protected function initExcelExport(): void
    {
        $this->excel = Excel::create();
    }

    /**
     * Generate mt ussage log excel export. When no customer is provider, it will export the data for all customers.
     * @throws Exception
     */
    public function excel(int $customerId = null): void
    {
        //load the export data
        $monthlySummaryByResource = $this->normalizeData(
            $this->usageSumLogger->loadMonthlySummaryByResource($customerId)
        );
        $documentUsage = $this->normalizeData(
            $this->taskUsageLog->loadByTypeAndCustomer($customerId, $this->documentTaskType)
        );
        $usageLogByCustomer = $this->normalizeData($this->usageLogger->loadByCustomer($customerId));

        //split the usage log by chunks. Each chunk is separate excel in the zip download
        $chunks = array_chunk($usageLogByCustomer, self::CHUNKS_BUFFER);
        $chunkIndex = 0;

        $this->setFileName($customerId);

        //if the export is splited in multiple excels, setup the export zip directory
        if ($hasChunks = count($chunks) > 1) {
            $this->setupZip();
        } else {
            $this->setupXls();
        }

        foreach ($chunks as $chunk) {
            $chunkIndex++;

            //if we should generate more then 1 excel, add the part number to it
            if ($hasChunks) {
                $this->setFileName($customerId);
                $this->spreadSheetName = $chunkIndex . '-' . $this->spreadSheetName;
            }

            // set property for export-filename
            //add the timestump to the export file
            if ($hasChunks) {
                $name = $this->zipFolder . $this->spreadSheetName . '.xlsx';
            } else {
                $name = $this->xlsFolder . $this->xlsName;
            }

            // Add sheets
            $this->excel->open($name, $hasChunks);
            $this->addUsageByMonthSheet($monthlySummaryByResource);
            $this->addUsageLogSheet($chunk);
            $this->addUsageInDocumentsSheet($documentUsage);
            $this->excel->close();

            // If no zip - just exit, because xlsx file is flushed into php://output by $this->excel->writer
            if (! $hasChunks) {
                return;
            }
            $this->worksheetIndex = 0;

            //initialize the Excel export data for another Excel file
            $this->initExcelExport();
        }
        //if it is chunk export, generate the zip and send it to download
        if ($hasChunks) {
            $this->downloadZip($this->zipName, $this->zipFolder);

            return;
        }

        $this->excel->open($this->xlsFolder . $this->xlsName, false);
        $this->excel->loadArrayData($this->labels, [[$this->translate->_('Es wurden keine Ergebnisse gefunden')]]);
        $this->excel->close();
    }

    /**
     * Get the data in format required for the tests. In the returned result, unneeded fields will be filtered.
     *
     * @throws ReflectionException
     */
    public function getExportRawDataTests(int $customerId = null): array
    {
        $monthlySummaryByResource = $this->usageSumLogger->loadMonthlySummaryByResource($customerId);
        $usageLogByCustomer = $this->usageLogger->loadByCustomer($customerId);
        $documentUsage = $this->taskUsageLog->loadByTypeAndCustomer($customerId, $this->documentTaskType);

        $unset = ["customerId", "yearAndMonth", "timestamp", "customers", "userGuid"];
        $languages = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $languages editor_Models_Languages */
        $languages = $languages->loadAllKeyValueCustom('id', 'rfc5646');

        //filter out and convert fields
        $filterRows = function ($needle, &$haystack) use ($languages) {
            foreach ($haystack as &$single) {
                foreach ($single as $key => &$value) {
                    if (in_array($key, $needle)) {
                        unset($single[$key]);
                    }
                    //convert the languages to rfc values
                    if (in_array($key, ['sourceLang', 'targetLang'])) {
                        $value = $languages[$value];
                    }
                }
            }
        };

        $filterRows($unset, $monthlySummaryByResource);
        $filterRows($unset, $usageLogByCustomer);
        $filterRows($unset, $documentUsage);

        return [
            'MonthlySummaryByResource' => $monthlySummaryByResource,
            'UsageLogByCustomer' => $usageLogByCustomer,
            'DocumentUsage' => $documentUsage,
        ];
    }

    public function setDocumentTaskType(array $newTaskType): void
    {
        $this->documentTaskType = $newTaskType;
    }

    /**
     * Add worksheet to the current spreedsheet
     * @throws Exception
     */
    protected function addWorkSheet(array $data, string $name, string $comment = ''): void
    {
        if (empty($data)) {
            $data[] = [
                'No results where found for the current worksheet' => '',
            ];
        }

        //add comment as separate column at the end of the first row
        if (! empty($comment)) {
            $data[0]['Info'] = $comment;
        }

        $this->excel->addWorksheet($this->worksheetIndex, $name);
        $this->excel->loadArrayData($this->labels, $data, $this->worksheetIndex);
        $this->worksheetIndex++;
    }

    /**
     * Setup zip folder for export.
     * This will also set the zipName and zipFolder properties
     */
    protected function setupZip(): void
    {
        //generate unique zip name
        $this->zipName = $this->spreadSheetName . '_' . str_replace(':', '.', NOW_ISO) . '.zip';
        //create the directory where the excel chunks will be saved
        $this->zipFolder = APPLICATION_DATA . '/tmp/' . $this->zipName . '/';
        is_dir($this->zipFolder) || mkdir($this->zipFolder);
    }

    /**
     * Setup xls folder for export.
     * This will also set the xlsName and xlsFolder properties
     */
    protected function setupXls(): void
    {
        // Generate unique xls name
        $this->xlsName = $this->spreadSheetName . '_' . str_replace(':', '.', NOW_ISO) . '.xlsx';

        // General the directory name where the excel file will be saved
        $this->xlsFolder = APPLICATION_DATA . '/tmp/';

        // Create that directory if it does not exist
        if (! is_dir($this->xlsFolder)) {
            mkdir($this->xlsFolder);
        }
    }

    /**
     * Generate the zip from chunk-export files and send the zip for download
     * @throws Exception
     */
    protected function downloadZip(string $name, string $path): void
    {
        $zip = new ZipArchive();
        $finalZip = $path . $name;
        $zipStatus = $zip->open($finalZip, ZIPARCHIVE::CREATE);
        if (! $zipStatus) {
            throw new Exception('E1335', [
                'path' => $finalZip,
                'errorCode' => $zipStatus,
            ]);
        }
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($files as $file) {
            //ignore dots
            if ($file->isDir()) {
                continue;
            }
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($path));

            $zip->addFile($filePath, $relativePath);
        }
        if (! $zip->close()) {
            throw new Exception('E1336', [
                'path' => $finalZip,
            ]);
        }
        Header::sendDownload(
            $name,
            'application/zip'
        );
        readfile($finalZip);

        //clean files
        $this->cleanExportZip($path);
    }

    /**
     * Remove the all produced files from the disk
     */
    protected function cleanExportZip(string $path): void
    {
        $iterator = new DirectoryIterator($path);
        foreach ($iterator as $fileinfo) {
            if ($fileinfo->isDot()) {
                continue;
            }
            if ($fileinfo->isFile()) {
                unlink($path . $fileinfo->getFilename());
            }
        }
        rmdir($path);
    }

    /**
     * Generate worksheet usage by month
     * @throws Exception
     */
    protected function addUsageByMonthSheet(array $data): void
    {
        //add row wich explains the current worksheet
        $comment = $this->translate->_(
            'Diese Daten enthalten alle Anfragen an Sprachressourcen, egal ob durch Aufgaben oder via InstantTranslate.'
        );
        $this->addWorkSheet($data, $this->translate->_('Ressourcen-Nutzung pro Monat'), $comment);
    }

    /**
     * Generate worksheet character usage per customer
     * @throws Exception
     */
    protected function addUsageLogSheet(array $data): void
    {
        //add row wich explains the current worksheet
        $comment = $this->translate->_(
            'Diese Daten enthalten alle Anfragen an Sprachressourcen, egal ob durch Aufgaben oder via InstantTranslate.'
        );
        $comment .= $this->translate->_('Jede Zeile korrespondiert mit einer Anfrage an eine Sprachressource.');
        $this->addWorkSheet($data, $this->translate->_('Log der Ressouren-Nutzung'), $comment);
    }

    /**
     * Generate worksheet task usage by document type
     * @throws Exception
     */
    protected function addUsageInDocumentsSheet(array $data): void
    {
        //add row wich explains the current worksheet
        $comment = $this->translate->_('Anzahl der mit InstantTranslate übersetzten Dokumente');
        $this->addWorkSheet($data, $this->translate->_('Dokumente pro Monat'), $comment);
    }

    private function setFileName(?int $customerId): void
    {
        if (! empty($customerId)) {
            $this->spreadSheetName = $this->translate->_(
                "Ressourcen-Nutzung fuer Kunde"
            ) . ' ' . $this->customersForNormalization[$customerId]['name'];
        } else {
            $this->spreadSheetName = $this->translate->_("Ressourcen-Nutzung fuer alle Kunden");
        }
    }

    private function normalizeData(array $dataSource): array
    {
        foreach ($dataSource as $idx => $line) {
            foreach ($line as $key => $value) {
                $line[$key] = $this->normalizeRow((string) $key, (string) $value);
            }
            $dataSource[$idx] = $line;
        }

        return $dataSource;
    }

    private function normalizeRow(string $key, string $value): string
    {
        return match ($key) {
            'sourceLang', 'targetLang' => $this->languageForNormalization[$value]['langName'] ?? $value,
            'repetition' => $value == 1 ? '✓' : '',
            'customerId' => $this->customersForNormalization[$value]['name'] ?? $value,
            'customers' => $this->normalizeCustomers($value),
            'userGuid' => $this->normalizeUsers($value),
            default => $value,
        };
    }

    private function normalizeCustomers(string $value): string
    {
        $customerIds = explode(',', trim($value, ','));
        $names = [];
        foreach ($customerIds as $id) {
            $names[] = $this->customersForNormalization[$id]['name'];
        }

        return implode(',', $names);
    }

    private function normalizeUsers(string $value): string
    {
        if ($value === ZfExtended_Models_User::SYSTEM_GUID) {
            return ZfExtended_Models_User::SYSTEM_LOGIN;
        }
        $u = $this->usersForNormalization[$value] ?? false;
        if (! $u) {
            return $value;
        }

        return $u['surName'] . ', ' . $u['firstName'] . ' (' . $u['login'] . ')';
    }
}
