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

use MittagQI\Translate5\Import\DirectoryLayoutInterface;

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *

 /**
 * Contains all parameters for the planned import.
 * Since this class is serialized for the worker table,
 *  it must not contain complex data!
 */
class editor_Models_Import_Configuration implements DirectoryLayoutInterface
{
    /***
     * Constant for the import directory folder name
     * @var string
     */
    public const WORK_FILES_DIRECTORY = 'workfiles';

    /***
     * Constant for the relais folder name in the import directory
     * @var string
     */
    public const RELAIS_FILES_DIRECTORY = 'relais';

    public const REFERENCE_FILES_DIRECTORY = 'referenceFiles';

    public const VISUAL_FILES_DIRECTORY = 'visual';

    /**
     * The max number of segments allowed in a trans-unit, if exceeded, the import will be rejected
     */
    public const MAX_SEGMENTS_PER_TRANSUNIT = 250;

    /**
     * @var editor_Models_Languages language entity instance
     */
    public $sourceLang;

    /**
     * @var editor_Models_Languages language entity instance
     */
    public $targetLang;

    /**
     * @var editor_Models_Languages language entity instance
     */
    public $relaisLang;

    /**
     * concrete given source language
     * @var mixed
     */
    public $sourceLangValue;

    /**
     * concrete given target language
     * @var mixed
     */
    public $targetLangValue;

    /**
     * concrete given relais language
     * @var mixed
     */
    public $relaisLangValue;

    /**
     * @var string import folder, under which the to be imported folder and file hierarchy resides
     */
    public $importFolder;

    /**
     * initial userguid of the segments
     * @var string
     */
    public $userGuid;

    /**
     * initial username of the segments
     * @var string
     */
    public $userName;

    /**
     * Worker Id of the import worker, usable as parentId for subsequent workers
     */
    public int $workerId;

    /**
     * if true, the uploaded files are only processed if the file extension is supported. Conversion plug-ins may disable that.
     * @var boolean
     */
    public $checkFileType = true;

    /**
     * Comma seperated list to add file-extensions to be ignored (APPLIES ONLY IF the filetype-check is disabled)
     * @var string
     */
    public $ignoredUncheckedExtensions = '';

    /***
     * The curent import uses depricated directory name
     * TODO:(23.02.2021 TRANSLATE-1596) remove me after the depricated support for "proofRead" is removed
     * @deprecated
     * @var string
     */
    public $isDeprecatedDirectoryName = false;

    /**
     * Container for additional file filters added automatically if defined here
     */
    public array $fileFilters = [];

    /**
     * needed internally for de/serialization
     * @var string
     */
    protected $usedLanguagetype;

    /**
     * populates the language fields
     * @param mixed $source
     * @param mixed $target
     * @param mixed $relais
     */
    public function setLanguages($source, $target, $relais, string $type)
    {
        $this->sourceLangValue = $source;
        $this->targetLangValue = $target;
        $this->relaisLangValue = $relais;

        $this->usedLanguagetype = $type;

        $langFields = [
            'sourceLang' => $source,
            'targetLang' => $target,
            'relaisLang' => $relais,
        ];

        foreach ($langFields as $key => $lang) {
            $langInst = ZfExtended_Factory::get(editor_Models_Languages::class);
            if (empty($lang) || ! $langInst->loadLang($lang, $type)) {
                //null setzen wenn Sprache nicht gefunden. Das triggert einen Fehler in der validateParams dieser Klasse
                $langInst = null;
            }
            $this->{$key} = $langInst;
        }
    }

    /**
     * returns the numeric (DB) ID of the requested language type (source|target|relais)
     * @param string $language one of [source,target,relais]
     * @return number
     */
    public function getLanguageId(string $language)
    {
        $field = $language . 'Lang';

        return isset($this->$field) ? $this->$field->getId() : 0;
    }

    /**
     * returns true if there is relais data and a language was found in the DB to the requested relais language
     * @return boolean
     */
    public function hasRelaisLanguage()
    {
        return ! empty($this->relaisLang);
    }

    public function isValid($taskGuid)
    {
        $this->validateParams($taskGuid);
        $this->validateImportFolders();
    }

    /**
     * Retrieves the required import files directory name.
     * If isDeprecatedDirectoryName is set to true, the depricated name will be used.
     * If isDeprecatedDirectoryNameLowercase, the depricate name will be used with lowercase.
     */
    public function getWorkfilesDirName(): string
    {
        /***
         * Remove this check after no longer proofRead support. This should return workfiles only.
         * TODO:(23.02.2021 TRANSLATE-1596) remove me after the depricated support for "proofRead" is removed
         * @deprecated
         */
        if ($this->isDeprecatedDirectoryName) {
            $config = Zend_Registry::get('config');

            return $config->runtimeOptions->import->proofReadDirectory;
        }

        //this stays after depricated is removed
        return self::WORK_FILES_DIRECTORY;
    }

    /**
     * Retrieves the absolute path to the workfiles-dir in the import-folder
     */
    public function getWorkfilesDir(): string
    {
        $prefix = $this->importFolder;
        $workfilesDir = $this->getWorkfilesDirName();

        return ($workfilesDir === '') ? $prefix : $prefix . DIRECTORY_SEPARATOR . $workfilesDir;
    }

    /**
     * returns the absolute path (incl. import root) to the reference files
     */
    public function getReferenceFilesDir(): string
    {
        $prefix = $this->importFolder;
        $refDir = editor_Models_Import_DirectoryParser_ReferenceFiles::getDirectory();

        return ($refDir === '') ? $prefix : $prefix . DIRECTORY_SEPARATOR . $refDir;
    }

    /**
     * Retrieves the base directory of the files to import
     */
    public function getImportDir(): string
    {
        return $this->importFolder;
    }

    /**
     * validiert / filtert die Get-Werte
     * @throws editor_Models_Import_ConfigurationException
     * @throws Zend_Validate_Exception|Zend_Exception
     */
    protected function validateParams($taskGuid): void
    {
        $guidValidator = new ZfExtended_Validate_Guid();
        $validateUsername = new Zend_Validate_Regex('"[A-Za-z0-9 \-]+"');
        if (! $guidValidator->isValid($taskGuid)) {
            //The given taskGuid was not valid GUID.
            throw new editor_Models_Import_ConfigurationException('E1035', [
                'taskGuid' => $taskGuid,
            ]);
        }
        if (! $guidValidator->isValid($this->userGuid)) {
            //The given userGuid was not valid GUID.
            throw new editor_Models_Import_ConfigurationException('E1036', [
                'userGuid' => $this->userGuid,
            ]);
        }
        if (! $validateUsername->isValid($this->userName)) {
            //The given userName was not valid user name.
            throw new editor_Models_Import_ConfigurationException('E1037', [
                'userName' => $this->userName,
            ]);
        }
        if (is_null($this->sourceLang)) {
            //The passed source language is not valid.
            throw new editor_Models_Import_ConfigurationException('E1032', [
                'language' => $this->sourceLangValue,
            ]);
        }
        if (is_null($this->targetLang)) {
            //The passed target language is not valid.
            throw new editor_Models_Import_ConfigurationException('E1033', [
                'language' => $this->targetLangValue,
            ]);
        }
    }

    /**
     * validiert die nötigen Import Verzeichnisse
     * @throws editor_Models_Import_ConfigurationException
     */
    protected function validateImportFolders()
    {
        if (! is_dir($this->importFolder)) {
            //The import root folder does not exist.
            throw new editor_Models_Import_ConfigurationException('E1038', [
                'folder' => $this->importFolder,
            ]);
        }
        //use the workfiles as review directory
        $reviewDir = $this->getWorkfilesDir();
        $data = [
            'review' => basename($reviewDir),
        ];

        /***
         * Remove this if check after depricated support is removed.
         * TODO:(23.02.2021 TRANSLATE-1596) remove me after the depricated support for "proofRead" is removed
         *  @deprecated
         */
        if (! is_dir($reviewDir)) {
            //workfiles is not valid directory, try the deprecated proofRead
            $this->isDeprecatedDirectoryName = true;

            $reviewDir = $this->getWorkfilesDir();
            $data = [
                'review' => basename($reviewDir),
            ];
        }
        if (! is_dir($reviewDir)) {
            /***
             * TODO:(23.02.2021 TRANSLATE-1596) remove me after the depricated support for "proofRead" is removed
             *  @deprecated
             */
            $this->isDeprecatedDirectoryName = false;

            //The imported package did not contain a valid review folder.
            throw new editor_Models_Import_ConfigurationException('E1039', [
                'review' => basename($this->getWorkfilesDir()),
            ]);
        }
        if (empty(glob($reviewDir . '/*'))) {
            //The imported package did not contain any files in the review folder.
            throw new editor_Models_Import_ConfigurationException('E1040', $data);
        }
    }

    /**
     * after deserialization we have to resurrect the language objects
     */
    public function __wakeup()
    {
        $this->setLanguages($this->sourceLangValue, $this->targetLangValue, $this->relaisLangValue, $this->usedLanguagetype);
    }

    /**
     * To reduce serialization data we remove the language instances and keep only the scalar values
     * @return string
     */
    public function __sleep()
    {
        $this->sourceLang = $this->targetLang = $this->relaisLang = null;

        return array_keys(get_class_vars(get_class($this)));
    }

    /***
     * Log a warning if the used work files directory is depricated
     * TODO:(23.02.2021 TRANSLATE-1596) remove me after the deprecated support for "proofRead" is removed
     * @param string $importDir
     */
    public function warnImportDirDeprecated(editor_Models_Task $task)
    {
        if ($this->isDeprecatedDirectoryName) {
            /** @var ZfExtended_Logger $logger */
            $logger = Zend_Registry::get('logger')->cloneMe('editor.import.configuration');
            $logger->warn(
                'E1338',
                'IMPORTANT: The "proofRead" folder in the zip import package is deprecated from now on. ' .
                'In the future please always use the new folder "workfiles" instead. All files that need to be ' .
                'reviewed or translated will have to be placed in the new folder "workfiles" from now on. ' .
                'In some future version of translate5 the support for "proofRead" folder will be completely removed. ' .
                'Currently it still is supported, but will write a "deprecated" message to the php error-log.',
                [
                    'task' => $task,
                ]
            );
        }
    }
}
