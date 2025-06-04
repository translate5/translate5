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
use editor_Models_Customer_Customer as Customer;
use editor_Models_LanguageResources_CustomerAssoc as CustomerAssoc;
use editor_Models_TermCollection_TermCollection as TermCollection;
use editor_Models_Terminology_Models_TermModel as Term;
use MittagQI\Translate5\LanguageResource\CustomerAssoc\CustomerAssocService;
use MittagQI\Translate5\Plugins\TermImport\DTO\InstructionsDTO;
use MittagQI\Translate5\Plugins\TermImport\Exception\TermImportException;
use MittagQI\Translate5\Plugins\TermImport\Service\LoggerService;
use MittagQI\Translate5\Plugins\TermImport\Services\Across\TbxSoapConnector;

class editor_Plugins_TermImport_Services_Import
{
    /***
     * Import from file system config file
     * @var string
     */
    public const FILESYSTEM_CONFIG_NAME = "filesystem.config";

    /***
     * Import from across api config name
     * @var string
     */
    public const CROSSAPI_CONFIG_NAME = "crossapi.config";

    /***
     * Import dir key from the filesystem config file
     * @var string
     */
    public const IMPORT_DIR_ARRAY_KEY = "importDir";

    /***
     * Key from the crossapi config file for the across api url
     * @var string
     */
    public const IMPORT_ACOSS_API_URL = "crossAPIurl";

    /***
     * Key for the merge terms flag used by the tbx import parser
     * @var string
     */
    public const IMPORT_MERGE_TERMS_KEY = "mergeTerms";

    /***
     *  Key from the crossapi config file for the across api user
     * @var string
     */
    public const IMPORT_ACOSS_API_USER = "apiUsername";

    /***
     *  Key from the crossapi config file for the across api password
     * @var string
     */
    public const IMPORT_ACOSS_API_PWD = "apiPassword";

    public const IMPORT_ACROSS_API_SSL_PEER_NAME = 'ssl_peer_name';

    public const IMPORT_ACROSS_API_SSL_ALLOW_SELF_SIGNED = 'ssl_allow_self_signed';

    /***
     * Key from the crossapi config file for the across export files directory
     * @var string
     */
    public const CROSS_EXPORT_FILES_DIR = "crossExportFilesDir";

    /***
     * File mapping group name in the filesystem config
     *
     * @var string
     */
    public const FILE_MAPPING_GROUP = "FileMapping";

    /***
     * Collection mapping group name in the filesystem config
     *
     * @var string
     */
    public const COLLECTION_MAPPING_GROUP = "CollectionMapping";

    /***
     * Tmp file name for the file from the across api
     */
    public const CROSS_API_TMP_FILENAME = "Apiresponsetmpfilename.tbx";

    /***
     * Deletes all terms in all listed termCollections, that have a modification date older than the listed one.
     * Since every term that exists in a TBX gets a new updated date on TBX-import, even if it is not changed: Simply set this date to yesterday to delete all terms, that are not part of the current import
     * The updated date is a date internal to translate5 and different from the modified date of the term, that is shown in the interface
     * @var string
     */
    public const DELETE_ENTRIES_KEY = "deleteTermsLastTouchedOlderThan";

    /***
     * Config key for deleting terms older than current import date.
     * @var string
     */
    public const DELETE_TERMS_OLDER_THAN_IMPORT_KEY = "deleteTermsOlderThanCurrentImport";

    /***
     * Config key for deletes all proposals older then deleteProposalsLastTouchedOlderThan date.
     *
     * @var string
     */
    public const DELETE_PROPOSALS_OLDER_THAN_KEY = "deleteProposalsLastTouchedOlderThan";

    /***
     * Config key form delete all proposals older than the NOW_ISO
     *
     * @var string
     */
    public const DELETE_PROPOSALS_OLDER_THAN_IMPORT_KEY = "deleteProposalsOlderThanCurrentImport";

    /***
     * Data from the filesystem or cross api config file
     * @var array
     */
    public $configMap = [];

    /**
     * @var float contains the start time of the last profiling call
     */
    protected $profilingStart = null;

    /**
     * messages return to caller
     * @var array
     */
    protected $returnMessage = [];

    private CustomerAssocService $customerAssocService;

    public function __construct()
    {
        //init profiling
        $this->logProfiling();

        $this->customerAssocService = CustomerAssocService::create();
    }

    /***
     * File system import handler.
     */
    public function handleFileSystemImport()
    {
        if (empty($this->configMap)) {
            $this->loadConfig(self::FILESYSTEM_CONFIG_NAME);
        }
        //tbx files import folder
        $importDir = $this->configMap[self::IMPORT_DIR_ARRAY_KEY];

        try {
            if (! file_exists($importDir) && ! @mkdir($importDir, 0777, true)) {
                return ["Unable to create the TBX Import dir or the TBX import directory is missing. Path: " . $importDir];
            }
        } catch (Throwable $e) {
            return ["Unable to create the TBX Import dir or the TBX import directory is missing. Path: " . $importDir];
        }

        if ($this->isFolderEmpty($importDir)) {
            return ["The configured import dir is empty"];
        }

        $this->returnMessage = [];

        $this->logProfiling('Init FileSystemImport');
        //get all files from the import direcotry
        $it = new FilesystemIterator($importDir, FilesystemIterator::SKIP_DOTS);
        $affectedCollections = [];
        foreach ($it as $fileinfo) {
            $file = $fileinfo->getFilename();

            $params = $this->handleCollectionForFile($file);

            if (! $params) {
                continue;
            }

            if (is_string($params)) {
                $this->returnMessage[] = $params;

                continue;
            }
            $affectedCollections[] = $params['collectionId'];
            $this->logProfiling('Prepared collection ' . $params['collectionName'] . '(' . $params['collectionId'] . ')');

            //define the import source, used for storing the file in the disk in the needed location
            $params['importSource'] = "filesystem";

            $this->importTbx($file, $importDir . $file, $params);

            //remove old term entries and terms
            $this->removeTermsOlderThenImport($params['collectionId']);

            //remove term proposals
            $this->removeProposalsOlderThan($params['collectionId']);
        }
        if (empty($this->returnMessage)) {
            $this->returnMessage[] = "No files where imported";
        }

        if (empty($affectedCollections)) {
            return $this->returnMessage;
        }

        //remove old terms
        $this->removeOldTerms($affectedCollections);

        //remove proposals older than current import
        $this->removeProposalsOlderThenImport($affectedCollections);

        //clean the empty term entries
        $this->removeEmptyTermEntries($affectedCollections);

        return $this->returnMessage;
    }

    /***
     * Import the tbx files into the term collection from the across via the across api.
     * The files will be imported in the configured collection in the crossapi config file
     *
     * @return string[]
     * @throws \MittagQI\Translate5\Plugins\TermImport\Services\Across\Exception
     */
    public function handleAccrossApiImport()
    {
        if (empty($this->configMap)) {
            $this->loadConfig(self::CROSSAPI_CONFIG_NAME);
        }

        $this->returnMessage = [];

        //tbx files import folder
        $apiUrl = $this->configMap[self::IMPORT_ACOSS_API_URL];
        $apiUser = $this->configMap[self::IMPORT_ACOSS_API_USER];
        $apiPwd = $this->configMap[self::IMPORT_ACOSS_API_PWD];

        if (empty($apiUrl)) {
            $this->returnMessage[] = "Across api url is not defined in the config file";

            return $this->returnMessage;
        }

        if (empty($apiUser) || empty($apiPwd)) {
            $this->returnMessage[] = "Authentication parameters are missing";

            return $this->returnMessage;
        }

        //tbx files import folder
        $exportFilesDir = $this->configMap[self::CROSS_EXPORT_FILES_DIR];

        try {
            if (! file_exists($exportFilesDir) && ! @mkdir($exportFilesDir, 0777, true)) {
                return ["Unable to create the TBX Import dir or the TBX import directory is missing. Path: " . $exportFilesDir];
            }
        } catch (Exception $e) {
            return ["Unable to create the TBX Import dir or the TBX import directory is missing. Path: " . $exportFilesDir];
        }

        if ($this->isFolderEmpty($exportFilesDir)) {
            $this->returnMessage[] = "Across api export files are not defined";

            return $this->returnMessage;
        }

        $additionalConfig = $this->getAdditionalConfig();

        //get all across export files from the dir
        $it = new FilesystemIterator($exportFilesDir, FilesystemIterator::SKIP_DOTS);
        $affectedCollections = [];
        $this->logProfiling('Init FileAcrossApiImport');
        foreach ($it as $fileinfo) {
            $file = $fileinfo->getFilename();

            $connector = new TbxSoapConnector($apiUrl, $apiUser, $apiPwd, $additionalConfig);

            $params = $this->handleCollectionForFile($file);

            if (! $params) {
                continue;
            }
            //if it is a string, set the error message
            if (is_string($params)) {
                $this->returnMessage[] = $params;

                continue;
            }

            if (! $params || ! isset($params['collectionId'])) {
                continue;
            }

            //absolute file path
            $file = $exportFilesDir . $file;

            $affectedCollections[] = $params['collectionId'];

            $respTbxl = $connector->getTbx($file);

            if (empty($respTbxl)) {
                $this->returnMessage[] = "Empty tbx file for across config file:" . $file;

                continue;
            }

            $tmpFile = $exportFilesDir . self::CROSS_API_TMP_FILENAME;

            //save the tmp file to the disc
            file_put_contents($tmpFile, $respTbxl);

            //define the import source, used for storing the file in the disk in the needed location
            $params['importSource'] = "crossapi";

            $this->importTbx($file, $tmpFile, $params);

            //remove old term entries and terms
            $this->removeTermsOlderThenImport($params['collectionId']);

            //remove term proposals
            $this->removeProposalsOlderThan($params['collectionId']);

            //remove the tmp file
            if (file_exists($tmpFile)) {
                unlink($tmpFile);
            }
        }
        if (empty($this->returnMessage)) {
            $this->returnMessage[] = "No files where imported";
        }

        if (empty($affectedCollections)) {
            return $this->returnMessage;
        }

        //remove old terms
        $this->removeOldTerms($affectedCollections);

        //remove proposals older than current import
        $this->removeProposalsOlderThenImport($affectedCollections);

        //clean the empty term entries
        $this->removeEmptyTermEntries($affectedCollections);

        return $this->returnMessage;
    }

    protected function importTbx($file, $absFile, $params)
    {
        $this->logProfiling();
        $model = ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
        /* @var $model editor_Models_TermCollection_TermCollection */
        if (isset($this->configMap[self::IMPORT_MERGE_TERMS_KEY])) {
            $params['mergeTerms'] = $this->configMap[self::IMPORT_MERGE_TERMS_KEY] === "true" || $this->configMap[self::IMPORT_MERGE_TERMS_KEY] === "1";
        }

        if ($model->importTbx([$absFile], $params)) {
            $msg = "File: " . $file . ' was imported in the collection: ' . $params['collectionName'];
            $this->returnMessage[] = $msg;
            error_log($msg);
        } else {
            $msg = "Unable to import the file: " . $file . " into the collection";
            $this->returnMessage[] = $msg;
            error_log("Unable to import the file: " . $file . " into the collection");
        }
        $this->logProfiling('Imported TBX');
    }

    /***
     * Check if for the current file there is config for the termcollection to tbx file association
     * and termcollection to customer number association
     * @param string $file: file to check
     * @return NULL|string|array
     */
    private function handleCollectionForFile($file)
    {
        if (! isset($this->configMap[self::FILE_MAPPING_GROUP]) || ! isset($this->configMap[self::FILE_MAPPING_GROUP][$file])) {
            return null;
        }

        $collectionName = $this->configMap[self::FILE_MAPPING_GROUP][$file];

        if (! isset($this->configMap[self::COLLECTION_MAPPING_GROUP][$collectionName])) {
            return "No customer is assigned to the collection:" . $collectionName;
        }

        $customerNumber = $this->configMap[self::COLLECTION_MAPPING_GROUP][$collectionName];

        $customer = ZfExtended_Factory::get('editor_Models_Customer_Customer');
        /* @var $customer editor_Models_Customer_Customer */
        $customer->loadByNumber($customerNumber);
        $customerId = (int) $customer->getId();

        $model = ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
        /* @var $model editor_Models_TermCollection_TermCollection */

        $tc = $model->loadByName($collectionName);

        //if the term collection exist, return the config import array array
        if (! empty($tc)) {
            $customerAssoc = ZfExtended_Factory::get('editor_Models_LanguageResources_CustomerAssoc');
            /* @var $customerAssoc editor_Models_LanguageResources_CustomerAssoc */
            $customers = $customerAssoc->loadByLanguageResourceId($tc['id']);
            $customers = array_column($customers, 'customerId');

            //check if the customer exist in the assoc table
            if (! in_array($customerId, $customers)) {
                $customers[] = $customerId;
                //add the customer to the assoc table for the term collection
                $this->customerAssocService->associate((int) $tc['id'], $customerId);
            }

            return [
                'collectionId' => $tc['id'],
                'customerIds' => $customers,
                'mergeTerms' => true,
                'collectionName' => $tc['name'],
            ];
        }

        //create new term collection/language resource
        $collection = $model->create($collectionName);

        $this->customerAssocService->associate((int) $collection->getId(), $customerId);

        return [
            'collectionId' => $collection->getId(),
            'customerIds' => [$customerId],
            'mergeTerms' => true,
            'collectionName' => $collectionName,
        ];
    }

    /**
     * Prepare temporary directory to be download-destination for tbx-files
     *
     * @throws \MittagQI\Translate5\Plugins\TermImport\Exception\TermImportException
     */
    public function prepareTbxDownloadTempDir(string $filesystemKeyDecoded): string
    {
        // Destination folder to download tbx-files from certain filesystem
        $dir = str_replace(DIRECTORY_SEPARATOR, '/', APPLICATION_DATA)
            . "/tbx-import/filesystem/$filesystemKeyDecoded";

        // If no dir exists so far - try to create it
        if (! file_exists($dir) && ! @mkdir($dir, 0777, true)) {
            throw new TermImportException('E1576', compact('dir'));
        }

        // Return download destination folder
        return $dir;
    }

    /**
     * @throws ReflectionException
     */
    public function import(InstructionsDTO $instructions, string $tbxDir, string $importSource, LoggerService $logger)
    {
        // Get term collection model
        $termCollectionM = ZfExtended_Factory::get(TermCollection::class);

        // Array of collections affected by the import
        $affectedCollections = [];

        // Array of names of successfully imported tbx files
        $importedTbxFiles = [];

        // Prepare shared params for the import
        $sharedParams = [
            'mergeTerms' => $instructions->mergeTerms,
            'importSource' => $importSource,
        ];

        // Foreach [$tbxFileName => $termCollectionName] pair left/kept in $instructions->FileMapping
        foreach ($instructions->FileMapping as $tbxFileName => $termCollectionName) {
            // Prepare import params by merging shared params with params specific to tbxFile's TermCollection
            $params = $sharedParams + $this->prepareCustomerAssocParams(
                $termCollectionName,
                $instructions->CollectionMapping[$termCollectionName] // Customer number
            );

            // Prepare absolute path to the current downloaded tbx file
            $tbxFilePath = "$tbxDir/$tbxFileName";

            // Append collectionId to the array of affected
            // todo: Decide whether it is this the right point to add collection to array of affected? looks like
            //       it's not, but it's done that way both in handleFilesystemImport() and handleAccrossApiImport()
            $affectedCollections[] = $params['collectionId'];

            //
            if ($termCollectionM->importTbx([$tbxFilePath], $params)) {
                $logger->fileImportSuccess($tbxFileName);
                $importedTbxFiles[$tbxFileName] = true;
            } else {
                $logger->fileImportFailure($tbxFileName);
            }

            // Remove old term entries and terms
            if ($instructions->deleteTermsOlderThanCurrentImport) {
                $this->deleteTermsOlderThanCurrentImport($params['collectionId']);
            }

            // Remove term proposals
            if ($olderThan = $instructions->deleteProposalsLastTouchedOlderThan) {
                $this->removeOldProposals([$params['collectionId']], $olderThan);
            }

            // Remove the tmp file
            if (file_exists($tbxFilePath)) {
                unlink($tbxFilePath);
            }
        }

        // If no files were imported - log that
        if (empty($importedTbxFiles)) {
            $logger->zeroImportedFiles($tbxDir);
        }

        // If we have affected collections
        if ($affectedCollections) {
            // Remove old terms, if need
            if ($olderThan = $instructions->deleteTermsLastTouchedOlderThan) {
                ZfExtended_Factory::get(Term::class)->removeOldTerms($affectedCollections, $olderThan);
                $this->removeCollectionTbxFromDisc($affectedCollections, strtotime($olderThan));
            }

            // Remove proposals older than current import
            if ($instructions->deleteProposalsOlderThanCurrentImport) {
                $this->removeOldProposals($affectedCollections, NOW_ISO);
            }

            // Clean the empty term entries
            $this->removeEmptyTermEntries($affectedCollections);
        }

        // Return array of tbx files that were successfully imported
        return $importedTbxFiles;
    }

    /**
     * @throws ReflectionException
     */
    private function prepareCustomerAssocParams(string $termCollectionName, string $customerNumber): array
    {
        /* @var $customer Customer */
        $customer = ZfExtended_Factory::get(Customer::class);
        $customer->loadByNumber($customerNumber);
        $customerId = (int) $customer->getId();

        /* @var $termCollection TermCollection */
        $termCollection = ZfExtended_Factory::get(TermCollection::class);
        $termCollectionI = $termCollection->loadByName($termCollectionName);
        $customers = [];

        // If the term collection exists
        if ($termCollectionI) {
            /* @var $customerAssoc CustomerAssoc */
            $customerAssoc = ZfExtended_Factory::get(CustomerAssoc::class);
            $customers = $customerAssoc->loadByLanguageResourceId($termCollectionI['id']);
            $customers = array_column($customers, 'customerId');

            // Add [termCollection <=> customer] assoc, if need
            if (! in_array($customerId, $customers)) {
                $customers[] = $customerId;
                $this->customerAssocService->associate((int) $termCollectionI['id'], $customerId);
            }

            // Else
        } else {
            // Create new term collection/language resource
            $termCollectionI = $termCollection
                ->create($termCollectionName)
                ->toArray();

            $this->customerAssocService->associate((int) $termCollectionI['id'], $customerId);
        }

        // Return param for tbx import
        return [
            'collectionId' => $termCollectionI['id'],
            'customerIds' => $customers,
        ];
    }

    private function deleteTermsOlderThanCurrentImport($collectionId)
    {
        /* @var $termModel editor_Models_Terminology_Models_TermModel */
        $termModel = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
        $termModel->removeOldTerms([$collectionId], NOW_ISO);

        // Clean the old tbx files from the disc
        $this->removeCollectionTbxFromDisc([$collectionId], strtotime(NOW_ISO));
    }

    private function loadConfig($configName)
    {
        $path = $this->getPluginConfigFolderPath();
        $this->initConfigFile($path . $configName);
    }

    /***
     * Init the config array
     *
     * @param string $filePath : absolute path to the config file
     *
     * @throws ZfExtended_ValidateException
     */
    private function initConfigFile($filePath)
    {
        if (! file_exists($filePath)) {
            throw new ZfExtended_ValidateException("Configuration file is missing:" . $filePath);
        }
        $file = file_get_contents($filePath);
        if (empty($file)) {
            throw new ZfExtended_ValidateException("The configuration file:" . $filePath . ' is empty.');
        }

        $this->configMap = parse_ini_file($filePath, true);
        if (empty($this->configMap)) {
            throw new ZfExtended_ValidateException("Wrong file structure in :" . $filePath);
        }
    }

    /***
     * Remove terms older than the configured date in the config file.
     * @param array $collectionIds
     * @param string $olderThan
     */
    private function removeOldTerms(array $collectionIds)
    {
        //check if delete old tasks is configured in the config file
        if (empty($this->configMap[self::DELETE_ENTRIES_KEY])) {
            return;
        }
        $olderThan = $this->configMap[self::DELETE_ENTRIES_KEY];

        $termModel = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
        /* @var $termModel editor_Models_Terminology_Models_TermModel */
        $termModel->removeOldTerms($collectionIds, $olderThan);
        //clean the old tbx files from the disc
        $this->removeCollectionTbxFromDisc($collectionIds, strtotime($olderThan));
        $this->logProfiling('removeOldTerms for collections ' . join(',', $collectionIds));
    }

    /***
     * Remove terms older than current date: NOW_ISO
     *
     * @param int $collectionId
     */
    private function removeTermsOlderThenImport($collectionId)
    {
        //check if delete old terms is configured in the config file
        if (empty($this->configMap[self::DELETE_TERMS_OLDER_THAN_IMPORT_KEY])) {
            return;
        }

        $termModel = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
        /* @var $termModel editor_Models_Terminology_Models_TermModel */
        $termModel->removeOldTerms([$collectionId], NOW_ISO);
        //clean the old tbx files from the disc
        $this->removeCollectionTbxFromDisc([$collectionId], strtotime(NOW_ISO));
        $this->logProfiling('removeTermsOlderThenImport for collection ' . $collectionId);
    }

    /***
     * Delete all proposals older than deleteProposalsLastTouchedOlderThan date.
     *
     * @param int $collectionId
     */
    private function removeProposalsOlderThan($collectionId)
    {
        //check if delete old entries is configured in the config file
        if (empty($this->configMap[self::DELETE_PROPOSALS_OLDER_THAN_KEY])) {
            return;
        }
        $olderThan = $this->configMap[self::DELETE_PROPOSALS_OLDER_THAN_KEY];
        ;
        $this->removeOldProposals([$collectionId], $olderThan);
        $this->logProfiling('removeProposalsOlderThan for collection ' . $collectionId);
    }

    /***
     * Remove proposals older than curent import
     * @param array $collectionIds
     */
    private function removeProposalsOlderThenImport(array $collectionIds)
    {
        //check if delete old terms is configured in the config file
        if (empty($this->configMap[self::DELETE_PROPOSALS_OLDER_THAN_IMPORT_KEY])) {
            return;
        }
        $this->removeOldProposals($collectionIds, NOW_ISO);
        $this->logProfiling('removeProposalsOlderThenImport for collection ' . print_r($collectionIds, 1));
    }

    /***
     * Remove empty term entries (term entries without any term in it).
     * Only the empty term entries from the same term collection will be removed.
     *
     * @param array $collectionIds
     */
    protected function removeEmptyTermEntries(array $collectionIds)
    {
        //remove all empty term entries from the same term collection
        $termEntry = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermEntryModel');
        /* @var $termEntry editor_Models_Terminology_Models_TermEntryModel */
        $termEntry->removeEmptyFromCollection($collectionIds);
        $this->logProfiling('removeEmptyTermEntries for collections ' . join(',', $collectionIds));
    }

    /***
     * Remove proposals for given collection and where the last change for the proposal is older than $olderThan date
     *
     * @param array $collectionIds
     * @param string $olderThan
     */
    protected function removeOldProposals(array $collectionIds, string $olderThan)
    {
        // Remove term proposals
        $term = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
        /* @var $term editor_Models_Terminology_Models_TermModel */
        $term->removeProposalsOlderThan($collectionIds, $olderThan);

        // Remove attribute proposals
        $attribute = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeModel');
        /* @var $attribute editor_Models_Terminology_Models_AttributeModel */
        $attribute->removeProposalsOlderThan($collectionIds, $olderThan);
    }

    /****
     * Remove term collection tbx files from the tbx-import directory older than the given timestamp
     * @param array $collections
     * @param int $olderThan
     */
    protected function removeCollectionTbxFromDisc(array $collections, int $olderThan)
    {
        $collection = ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
        /* @var $collection editor_Models_TermCollection_TermCollection */
        foreach ($collections as $c) {
            $collection->removeOldCollectionTbxFiles($c, $olderThan);
        }
        $this->logProfiling('removeCollectionTbxFromDisc for collections ' . join(',', $collections));
    }

    /***
     * Get the plugin config folder absolute path
     * @return string
     */
    private function getPluginConfigFolderPath()
    {
        return APPLICATION_PATH . '/modules/editor/Plugins/TermImport/config/';
    }

    /**
     * Check if the folder contains file
     * @param string $dir
     * @return boolean
     */
    private function isFolderEmpty($dir)
    {
        return (($files = @scandir($dir)) && count($files) <= 2);
    }

    /**
     * logs a message to the error log and prints the duration needed
     * @param string $msg if empty just reset start timer and log nothing
     */
    protected function logProfiling($msg = null)
    {
        if (! empty($msg)) {
            $duration = microtime(true) - $this->profilingStart;
            error_log('Profiling TermPortal Import - ' . $msg . ": \n  Duration (seconds): " . $duration);
        }
        $this->profilingStart = microtime(true);
    }

    private function getAdditionalConfig(): array
    {
        $additionalConfig = [];
        $hasPeerName = ! empty($this->configMap[self::IMPORT_ACROSS_API_SSL_PEER_NAME]);
        $isSelfSignedAllowed = ! empty($this->configMap[self::IMPORT_ACROSS_API_SSL_ALLOW_SELF_SIGNED]);
        if (! $hasPeerName && ! $isSelfSignedAllowed) {
            return $additionalConfig;
        }

        $ssl = [];
        if ($hasPeerName) {
            $ssl['peer_name'] = $this->configMap[self::IMPORT_ACROSS_API_SSL_PEER_NAME];
        }
        if ($isSelfSignedAllowed) {
            $ssl['verify_peer'] = false;
            $ssl['verify_peer_name'] = false;
            $ssl['allow_self_signed'] = true;
        }

        $additionalConfig['stream_context'] = stream_context_create([
            'ssl' => $ssl,
        ]);

        return $additionalConfig;
    }
}
