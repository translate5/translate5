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

use MittagQI\Translate5\LanguageResource\CustomerAssoc\CustomerAssocService;
use MittagQI\Translate5\LanguageResource\TaskAssociation;

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *

/**
 * Collect the terms and the terms attributes from the tbx file and save them to the database
 *
 */
class editor_Models_Import_TermListParser_Tbx implements editor_Models_Import_MetaData_IMetaDataImporter
{
    public const TBX_ARCHIV_NAME = 'terminology.tbx';

    protected editor_Models_Task $task;

    protected array $languages = [];

    /**
     * Das Array beinhaltet eine Zuordnung der in TBX möglichen Term Stati zu den im Editor verwendeten
     */
    protected array $statusMap = [
        'preferredTerm' => editor_Models_Terminology_Models_TermModel::STAT_PREFERRED,
        'admittedTerm' => editor_Models_Terminology_Models_TermModel::STAT_ADMITTED,
        'legalTerm' => editor_Models_Terminology_Models_TermModel::STAT_LEGAL,
        'regulatedTerm' => editor_Models_Terminology_Models_TermModel::STAT_REGULATED,
        'standardizedTerm' => editor_Models_Terminology_Models_TermModel::STAT_STANDARDIZED,
        'deprecatedTerm' => editor_Models_Terminology_Models_TermModel::STAT_DEPRECATED,
        'supersededTerm' => editor_Models_Terminology_Models_TermModel::STAT_SUPERSEDED,

        //some more states (uncompleted!), see TRANSLATE-714
        'proposed' => editor_Models_Terminology_Models_TermModel::STAT_PREFERRED,
        'deprecated' => editor_Models_Terminology_Models_TermModel::STAT_DEPRECATED,
        'admitted' => editor_Models_Terminology_Models_TermModel::STAT_ADMITTED,
    ];

    /**
     * Will be set in first <termEntry> of the tbx-file.
     * Detects if ids should be added to the termEntries or not
     * @var boolean
     */
    protected bool $addTermEntryIds = true;

    /**
     * Will be set in first <term> of the tbx-file.
     * Detects if ids should be added to the terms or not
     * @var boolean
     */
    protected bool $addTermIds = true;

    /***
     * The customers of the term collection
     *
     * @var array
     */
    public array $customerIds = [];

    /***
     * Flag if the unfounded terms in the termCollection should be merged.
     * If the parameter is true, translate5 will search all existing terms to see if the same term already exists in the TermCollection in the same language
     * If the parameter is false, a new term is added with the termEntry ID and term ID from the TBX in the TermCollection
     * @var boolean
     */
    public bool $mergeTerms = false;

    /***
     * Resource import source. It is used to make the difference between filesystem import and crossapi import
     * @var string
     */
    public string $importSource = "";

    /***
     * @var editor_Models_TermCollection_TermCollection
     */
    protected $termCollection;

    /***
     * @var $logger ZfExtended_Logger
     */
    protected ZfExtended_Logger $logger;

    /**
     * @var Zend_Config
     */
    protected $config;

    /***
     *
     * @var ZfExtended_Models_User
     */
    protected $user;

    /***
     * Term entry model instance (this is a helper instance)
     *
     * @var editor_Models_Terminology_Models_TermEntryModel
     */
    protected $termEntryModel;

    /***
     * Term model instance (this is a helper instance)
     *
     * @var editor_Models_Terminology_Models_TermModel
     */
    protected $termModel;

    /**
     * @var editor_Models_Terminology_Import_TbxFileImport
     */
    protected $tbxFileImport;

    /**
     * editor_Models_Import_TermListParser_Tbx constructor.
     * @throws Zend_Exception
     */
    public function __construct()
    {
        if (! defined('LIBXML_VERSION') || LIBXML_VERSION < '20620') {
            //Mindestversion siehe http://www.php.net/manual/de/xmlreader.readstring.php
            throw new Zend_Exception('LIBXML_VERSION must be at least 2.6.20 (or as integer 20620).');
        }
        $this->config = Zend_Registry::get('config');

        //init the logger (this will write in the language resources log and in the main log)
        $this->logger = Zend_Registry::get('logger')->cloneMe('editor.terminology.import');
        $this->user = ZfExtended_Factory::get('ZfExtended_Models_User');
        $this->termCollection = ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
        $this->termEntryModel = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermEntryModel');
        $this->termModel = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
    }

    /**
     * invocation of the automatic TBX import and term collection creation on the task import with a there provided TBX in the task ZIP.
     * (non-PHPdoc)
     * @see editor_Models_Import_MetaData_IMetaDataImporter::import()
     * @throws editor_Models_Import_TermListParser_Exception
     */
    public function import(editor_Models_Task $task, editor_Models_Import_MetaData $meta)
    {
        $tbxFilterRegex = '/\.tbx$/i';
        $tbxfiles = $meta->getMetaFileToImport($tbxFilterRegex);
        if (empty($tbxfiles)) {
            return;
        }

        $this->task = $task;

        //the termcollection customer is the one in the task
        if (empty($this->customerIds)) {
            $this->customerIds = [$this->task->getCustomerId()];
        }

        $this->loadUser($task->getPmGuid());

        //create term collection for the task and customer
        //the term collection will be created with autoCreateOnImport flag
        $collection = $this->termCollection->create("Term Collection for " . $this->task->getTaskName());

        CustomerAssocService::create()->associateCustomers((int) $collection->getId(), $this->customerIds);

        //add termcollection to task assoc
        $this->termCollection->addTermCollectionTaskAssoc($this->termCollection->getId(), $task->getTaskGuid());

        //reset the taskHash for the task assoc of the current term collection
        $this->resetTaskTbxHash();

        //all tbx files in the same term collection
        foreach ($tbxfiles as $file) {
            if (! $file->isReadable()) {
                throw new editor_Models_Import_TermListParser_Exception('E1023', [
                    'filename' => $file,
                    'languageResource' => $this->termCollection,
                ]);
            }
            $this->task->setTerminologie(1);

            //languages welche aus dem TBX importiert werden sollen
            $this->languages[$meta->getSourceLang()->getId()] = $this->normalizeLanguage($meta->getSourceLang()->getRfc5646());
            $this->languages[$meta->getTargetLang()->getId()] = $this->normalizeLanguage($meta->getTargetLang()->getRfc5646());

            //start with file parse
            $this->parseTbxFile([$file->getPathname()], $this->termCollection->getId());

            //check if the languages in the task are valid for the term collection
            $this->validateTbxLanguages();
        }
    }

    /***
     * Parse the tbx file and save the term, term attribute and term entry attribute in the database.
     * @param array $filePath : the path of the tbx files
     * @param string $termCollectionId : the database id of the term collection
     * @return bool
     */
    public function parseTbxFile(array $filePath, string $termCollectionId): bool
    {
        //if something is wrong with the file parse,
        try {
            $this->termCollection = ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
            $this->termCollection->load($termCollectionId);

            //reset the taskHash for the task assoc of the current term collection
            $this->resetTaskTbxHash();

            foreach ($filePath as $path) {
                $this->tbxFileImport = ZfExtended_Factory::get('editor_Models_Terminology_Import_TbxFileImport');

                $tmpName = $path['tmp_name'] ?? $path;
                $fileName = $path['name'] ?? null;

                //save the imported tbx to the disc
                $this->saveFileLocal($tmpName, $fileName);

                $termEntryCount = $this->tbxFileImport->importXmlFile($tmpName, $this->termCollection, $this->user, $this->mergeTerms);
                if ($termEntryCount <= 0) {
                    $this->logger->warn(
                        'E1028',
                        'The currently imported TBX file "{fileName}" did not contain any term entries!',
                        $this->getDefaultLogData([
                            'fileName' => $fileName ?? basename($tmpName),
                        ])
                    );
                }

                $this->updateCollectionLanguage();

                if ($termEntryCount <= 0) {
                    return true;
                }
            }
        } catch (Throwable $e) {
            $this->logger->exception($e, [
                'level' => ZfExtended_Logger::LEVEL_ERROR,
                'extra' => [
                    'languageResource' => $this->termCollection,
                ],
            ]);

            return false;
        }

        return true;
    }

    /**
     * checks if the needed TBX file exists, otherwise recreate if from DB
     * @throws editor_Models_Term_TbxCreationException
     */
    public function assertTbxExists(editor_Models_Task $task, SplFileInfo $tbxPath): string
    {
        //fallback for recreation of TBX file:
        $tbxData = $this->termModel->exportForTagging($task);

        $meta = $task->meta();
        $hash = md5($tbxData);
        $meta->setTbxHash($hash);
        $meta->save();

        file_put_contents($tbxPath, $tbxData);

        return $tbxData;
    }

    /**
     * returns the path to the archived TBX file
     */
    public static function getTbxPath(editor_Models_Task $task): string
    {
        return $task->getAbsoluteTaskDataPath() . DIRECTORY_SEPARATOR . self::TBX_ARCHIV_NAME;
    }

    /**
     * Die Methode implementiert folgenden Algorithmus
     *  - Sprachen die nicht verwendet werden ignorieren
     *  - Im Lektorat eingestellt:
     *   de => importiert de-de de de-at etc.pp.
     *   de-de => importiert de-de de
     *   restliche Sprachen ignorieren => return false
     *
     *   FIXME Performance: foreach term this loop is called!
     */

    /**
     * normalisiert den übergebenen Sprachstring für die interne Verwendung.
     * => strtolower
     * => trennt die per - oder _ getrennten Bestandteile in ein Array auf
     */
    protected function normalizeLanguage(string $langString): array
    {
        return explode('-', strtolower(str_replace('_', '-', $langString)));
    }

    /**
     * returns the status map
     */
    public function getStatusMap(): array
    {
        return $this->statusMap;
    }

    /**
     * returns the default logging extra data for logging in this class
     * @param array $data give additional data to be used here
     */
    private function getDefaultLogData(array $data = []): array
    {
        $data['languageResource'] = $this->termCollection;
        $data['userGuid'] = $this->user->getUserGuid();
        $data['userName'] = $this->user->getUserName();
        if (! empty($this->task)) {
            $data['task'] = $this->task;
        }

        return $data;
    }

    /**
     * Validate import languages against tbx languages
     */
    private function validateTbxLanguages(): void
    {
        $collection = ZfExtended_Factory::get(editor_Models_TermCollection_TermCollection::class);

        $collLangs = $collection->getLanguagesInTermCollections([
            $this->termCollection->getId(),
        ]);

        //disable terminology when no terms for the term collection are available
        if (empty($collLangs)) {
            $this->logger->error('E1028', 'Terminology for task is disabled because no languages are defined in the automatically created and attached term collection', $this->getDefaultLogData());
            $this->task->setTerminologie(0);

            return;
        }

        $collLangKeys = array_column($collLangs, 'id');

        $fuzzyModel = ZfExtended_Factory::get(editor_Models_Languages::class);

        // get all task source/target languages including the fuzzy languages
        $sourceLanguages = array_merge(
            $fuzzyModel->getFuzzyLanguages($this->task->getSourceLang(), includeMajor: true)
        );
        $targetLanguages = array_merge(
            $fuzzyModel->getFuzzyLanguages($this->task->getTargetLang(), includeMajor: true)
        );

        $matchSource = array_intersect($sourceLanguages, $collLangKeys);
        $matchTarget = array_intersect($targetLanguages, $collLangKeys);

        // check if the task source and target language can be found in the collection languages
        if (! empty($matchSource) && ! empty($matchTarget)) {
            return;
        }

        $this->logger->error(
            'E1028',
            'Terminology for task is disabled because the automatically created and attached term collection does not contain suitable languages for the task.',
            $this->getDefaultLogData([
                'taskLanguages' => $fuzzyModel->toRfcArray(array_merge($sourceLanguages, $targetLanguages)),
                'collectionLanguages' => $fuzzyModel->toRfcArray($collLangKeys),
            ])
        );
        $this->task->setTerminologie(0);
    }

    /***
     * Update term collection languages assoc.
     */
    protected function updateCollectionLanguage()
    {
        //remove old language assocs
        $assoc = ZfExtended_Factory::get('editor_Models_LanguageResources_Languages');
        /* @var $assoc editor_Models_LanguageResources_Languages */
        $assoc->removeByResourceId([$this->termCollection->getId()]);
        //add the new language assocs
        $this->termModel->updateAssocLanguages([$this->termCollection->getId()]);
    }

    /***
     * Save the imported file to the disk.
     * The file location will be "trasnalte5 parh" /data/tbx-import/tbx-for-filesystem-import/tc_"collectionId"/the file"
     *
     * @param string $filepath: source file location
     * @param string|null $name: source file name
     */
    private function saveFileLocal(string $filepath, string $name = null)
    {
        //if import source is not defined save it in filesystem folder
        if (! $this->importSource) {
            $this->importSource = "filesystem";
        }

        $tbxImportDirectoryPath = APPLICATION_PATH . '/../data/tbx-import/';
        $newFilePath = $tbxImportDirectoryPath . 'tbx-for-' . $this->importSource . '-import/tc_' . $this->termCollection->getId();

        //check if the directory exist and it is writable
        if (is_dir($tbxImportDirectoryPath) && ! is_writable($tbxImportDirectoryPath)) {
            $this->logger->error(
                'E1028',
                'Unable to save the tbx file to the tbx import path. The file is not writable. Import path: {importPath}',
                $this->getDefaultLogData([
                    'importPath' => $tbxImportDirectoryPath,
                ])
            );

            return;
        }

        try {
            $couldNotCreate = ! file_exists($newFilePath) && ! @mkdir($newFilePath, 0777, true);
        } catch (Throwable $e) {
            $couldNotCreate = false;
            $this->logger->exception($e);
        }

        if ($couldNotCreate) {
            $this->logger->error(
                'E1028',
                'Unable to create directory for imported tbx files. Directory path: {importPath}',
                $this->getDefaultLogData([
                    'importPath' => $newFilePath,
                ])
            );

            return;
        }

        $fi = new FilesystemIterator($newFilePath, FilesystemIterator::SKIP_DOTS);
        //if the name is set, use it as filename
        if ($name) {
            $fileName = iterator_count($fi) . '-' . $name;
        } else {
            $fileName = iterator_count($fi) . '-' . basename($filepath);
        }

        $newFileName = $newFilePath . '/' . $fileName;

        //copy the new file (rename probably not possible, if whole import folder is readonly in folder based imports)
        copy($filepath, $newFileName);
    }

    public function loadUser(string $userGuid)
    {
        $this->user->loadByGuid($userGuid);
    }

    /***
     * Reset the tbx hash for the tasks using the current term collection
     */
    protected function resetTaskTbxHash()
    {
        $taskassoc = ZfExtended_Factory::get('MittagQI\Translate5\LanguageResource\TaskAssociation');
        /* @var $taskassoc TaskAssociation */
        $assocs = $taskassoc->getAssocTasksByLanguageResourceId($this->termCollection->getId());
        if (empty($assocs)) {
            return;
        }
        $affectedTasks = array_column($assocs, 'taskGuid');
        $meta = ZfExtended_Factory::get('editor_Models_Task_Meta');
        /* @var $meta editor_Models_Task_Meta */
        $meta->resetTbxHash($affectedTasks);
    }

    /**
     * Get filesystem imported collection directory
     */
    public static function getFilesystemCollectionDir(): string
    {
        return APPLICATION_PATH . '/../data/tbx-import/tbx-for-filesystem-import/';
    }

    /**
     * Get collection import base directories
     */
    public static function getCollectionImportBaseDirectories(): array
    {
        return glob(APPLICATION_DATA . '/tbx-import/' . 'tbx-for-*-import', GLOB_ONLYDIR);
    }
}
