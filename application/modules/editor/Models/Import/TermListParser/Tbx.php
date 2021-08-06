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
 * Collect the terms and the terms attributes from the tbx file and save them to the database
 *
 */
class editor_Models_Import_TermListParser_Tbx implements editor_Models_Import_MetaData_IMetaDataImporter {
    const TBX_ARCHIV_NAME = 'terminology.tbx';

    /**
     * @var XmlReader
     */
    protected XMLReader $xml;

    /**
     * @var editor_Models_Task
     */
    protected  editor_Models_Task $task;

    /**
     * @var array
     */
    protected array $languages = [];


    /**
     * Das Array beinhaltet eine Zuordnung der in TBX möglichen Term Stati zu den im Editor verwendeten
     * @var array
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
     * collected term states not listed in statusMap
     * @var array
     */
    protected array $unknownStates = [];

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
    protected $logger;

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

    /** @var editor_Models_Terminology_Import_TbxFileImport  */
    protected $tbxFileImport;

    /**
     * editor_Models_Import_TermListParser_Tbx constructor.
     * @throws Zend_Exception
     */
    public function __construct()
    {
        if(!defined('LIBXML_VERSION') || LIBXML_VERSION < '20620') {
            //Mindestversion siehe http://www.php.net/manual/de/xmlreader.readstring.php
            throw new Zend_Exception('LIBXML_VERSION must be at least 2.6.20 (or as integer 20620).');
        }
        $this->config = Zend_Registry::get('config');

        //init the logger (this will write in the language resources log and in the main log)
        $this->logger = Zend_Registry::get('logger');
        $this->user = ZfExtended_Factory::get('ZfExtended_Models_User');
        $this->termCollection = ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
        $this->termEntryModel = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermEntryModel');
        $this->termModel = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
        $this->tbxFileImport = ZfExtended_Factory::get('editor_Models_Terminology_Import_TbxFileImport');
    }

    /**
     * Imports the tbx files into the term collection
     * (non-PHPdoc)
     * @see editor_Models_Import_MetaData_IMetaDataImporter::import()
     * @param editor_Models_Task $task
     * @param editor_Models_Import_MetaData $meta
     * @throws editor_Models_Import_TermListParser_Exception
     */
    public function import(editor_Models_Task $task, editor_Models_Import_MetaData $meta)
    {
        $tbxFilterRegex = '/\.tbx$/i';
        $tbxfiles = $meta->getMetaFileToImport($tbxFilterRegex);
        if(empty($tbxfiles)){
            return;
        }

        $this->task = $task;

        //the termcollection customer is the one in the task
        if(empty($this->customerIds)){
            $this->customerIds=[$this->task->getCustomerId()];
        }

        $this->loadUser($task->getPmGuid());

        //create term collection for the task and customer
        //the term collection will be created with autoCreateOnImport flag
        $this->termCollection->create("Term Collection for ".$this->task->getTaskName(), $this->customerIds);

        //add termcollection to task assoc
        $this->termCollection->addTermCollectionTaskAssoc($this->termCollection->getId(), $task->getTaskGuid());

        //reset the taskHash for the task assoc of the current term collection
        $this->resetTaskTbxHash($this->termCollection->getId());

        //all tbx files in the same term collection
        foreach($tbxfiles as $file) {
            if(!$file->isReadable()){
                throw new editor_Models_Import_TermListParser_Exception('E1023',[
                    'filename' => $file,
                    'languageResource' => $this->termCollection
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

        if(!empty($this->unknownStates)) {
            $this->log('TBX contains the following unknown term states: '.join(', ', $this->unknownStates));
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

            foreach ($filePath as $path){
                $tmpName = $path['tmp_name'] ?? $path;
                $fileName = $path['name'] ?? null;

                //save the imported tbx to the disc
                $this->saveFileLocal($tmpName, $fileName);

                $tbxAsSimpleXml = $this->tbxFileImport->importXmlFile($tmpName, $this->termCollection, $this->user, $this->mergeTerms);

                $this->updateCollectionLanguage();
            }
        } catch (Throwable $e){
            $this->logger->exception($e,[
                'level' => ZfExtended_Logger::LEVEL_ERROR,
                'extra' => ['languageResource' => $this->termCollection]
            ]);
            return false;
        }

        return true;
    }

    /**
     * checks if the needed TBX file exists, otherwise recreate if from DB
     * @param editor_Models_Task $task
     * @param SplFileInfo $tbxPath
     * @return string
     */
    public function assertTbxExists(editor_Models_Task $task, SplFileInfo $tbxPath): string
    {
        //fallback for recreation of TBX file:
        $tbxData = $this->termModel->exportForTagging($task);

        $meta = $task->meta();
        //ensure existence of the tbxHash field
        $meta->addMeta('tbxHash', $meta::META_TYPE_STRING, null, 'Contains the MD5 hash of the original imported TBX file before adding IDs', 36);

        $hash = md5($tbxData);
        $meta->setTbxHash($hash);
        $meta->save();

        file_put_contents($tbxPath, $tbxData);

        return $tbxData;
    }

    /**
     * returns the path to the archived TBX file
     * @param editor_Models_Task $task
     * @return string
     */
    public static function getTbxPath(editor_Models_Task $task): string
    {
        return $task->getAbsoluteTaskDataPath().DIRECTORY_SEPARATOR.self::TBX_ARCHIV_NAME;
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
     *
     */

    /**
     * normalisiert den übergebenen Sprachstring für die interne Verwendung.
     * => strtolower
     * => trennt die per - oder _ getrennten Bestandteile in ein Array auf
     * @param string $langString
     * @return array
     */
    protected function normalizeLanguage(string $langString): array
    {
        return explode('-',strtolower(str_replace('_','-',$langString)));
    }

    /**
     * returns the status map
     * @return array
     */
    public function getStatusMap(): array
    {
        return $this->statusMap;
    }


    /***
     * Ignore the tag of type figure.
     * TODO: if more tags need to be ignored, extend this!
     * @return boolean
     */
    protected function isIgnoreTag(){
        if ($this->isStartTag()) {
            return $this->xml->getAttribute('type') === "figure";
        }
        return false;
    }

    protected function log($logMessage, $code = 'E1028')
    {
        $data = ['languageResource' => $this->termCollection];
        if(!empty($this->task)){
            $data['task'] = $this->task;
        }
        $data['userGuid'] = $this->user->getUserGuid();
        $data['userName'] = $this->user->getUserName();
        $this->logger->info($code, $logMessage, $data);
    }

    /***
     * Validate import languages against tbx languages
     * @return boolean
     */
    private function validateTbxLanguages(): bool
    {
        $langs = [];
        $langs[$this->task->getSourceLang()] = $this->task->getSourceLang();
        $langs[$this->task->getTargetLang()] = $this->task->getTargetLang();

        $collection = ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
        /* @var $collection editor_Models_TermCollection_TermCollection */
        $collLangs = $collection->getLanguagesInTermCollections(array($this->termCollection->getId()));

        //disable terminology when no terms for the term collection are available
        if (empty($collLangs)) {
            $this->log("Terminology is disabled because no terms in the termcollection are found. TermCollectionId: ".$this->termCollection->getId());
            $this->task->setTerminologie(0);
            return false;
        }

        $collLangKeys = [];
        foreach ($collLangs as $lng){
            $collLangKeys[$lng['id']] = $lng['id'];
        }

        //missing langs
        $notProcessed = array_diff(
            array_keys($langs),
            array_keys($collLangKeys));

        if (empty($notProcessed)) {
            return true;
        }

        $this->log('For the following languages no term has been found in the tbx file: '.implode(', ', $notProcessed));
        $this->task->setTerminologie(0);
        return false;
    }

    /***
     * Update term collection languages assoc.
     */
    protected  function updateCollectionLanguage()
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
        if (!$this->importSource) {
            $this->importSource="filesystem";
        }

        $tbxImportDirectoryPath = APPLICATION_PATH.'/../data/tbx-import/';
        $newFilePath = $tbxImportDirectoryPath.'tbx-for-'.$this->importSource.'-import/tc_'.$this->termCollection->getId();

        //check if the directory exist and it is writable
        if (is_dir($tbxImportDirectoryPath) && !is_writable($tbxImportDirectoryPath)) {
            $this->log("Unable to save the tbx file to the tbx import path. The file is not writable. Import path: ".$tbxImportDirectoryPath." , termcollectionId: ".$this->termCollection->getId());
            return;
        }

        try {
            if (!file_exists($newFilePath) && !@mkdir($newFilePath, 0777, true)) {
                $this->log("Unable to create directory for imported tbx files. Directory path: ".$newFilePath." , termcollectionId: ".$this->termCollection->getId());
                return;
            }
        } catch (Exception $e) {
            $this->log("Unable to create directory for imported tbx files. Directory path: ".$newFilePath." , termcollectionId: ".$this->termCollection->getId());
            return;
        }

        $fi = new FilesystemIterator($newFilePath, FilesystemIterator::SKIP_DOTS);
        $fileName = null;
        //if the name is set, use it as filename
        if ($name) {
            $fileName = iterator_count($fi).'-'.$name;
        } else {
            $fileName = iterator_count($fi).'-'.basename($filepath);
        }

        $newFileName = $newFilePath.'/'.$fileName;

        //copy the new file (rename probably not possible, if whole import folder is readonly in folder based imports)
        copy($filepath, $newFileName);

    }

    /**
     * @param string $userGuid
     */
    public function loadUser(string $userGuid)
    {
        $this->user->loadByGuid($userGuid);
    }

    /***
     * Reset the tbx hash for the tasks using the current term collection
     */
    protected function resetTaskTbxHash()
    {
        $taskassoc = ZfExtended_Factory::get('editor_Models_LanguageResources_Taskassoc');
        /* @var $taskassoc editor_Models_LanguageResources_Taskassoc */
        $assocs = $taskassoc->getAssocTasksByLanguageResourceId($this->termCollection->getId());
        if (empty($assocs)){
            return;
        }
        $affectedTasks = array_column($assocs, 'taskGuid');
        $meta = ZfExtended_Factory::get('editor_Models_Task_Meta');
        /* @var $meta editor_Models_Task_Meta */
        $meta->resetTbxHash($affectedTasks);
    }

    /**
     * Get filesystem imported collection directory
     * @return string
     */
    static public function getFilesystemCollectionDir(): string
    {
        return APPLICATION_PATH.'/../data/tbx-import/tbx-for-filesystem-import/';
    }

    /***
     * Path to term collection images folder on the disk
     * @return string
     */
    static public function getFilesystemImagesDir(): string
    {
        return APPLICATION_PATH.'/../data/tbx-import/term-images-public/';
    }
}
