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

/**
 * Processes uploaded files on task creation and prepares a data provider for further processing
 */
final class editor_Models_Import_UploadProcessor
{
    public const TYPE_ZIP = 'zip';

    /**
     * The fieldname for the generic field for TBX upload
     * @var string
     */
    public const FIELD_TBX = 'importTbx';

    public const FIELD_CONFIG = 'taskConfig';

    public const ERROR_INVALID_FILE = 'noValidUploadFile';

    public const ERROR_EMPTY_FILE = 'emptyUploadFile';

    public const ERROR_NO_WORKFILES = 'fileUploadErrorIniSize';

    /**
     * Holds a processor for each processed task
     * This is handled here and not in the task-entity as this is only relevant in the import-phase
     * @var editor_Models_Import_UploadProcessor[]
     */
    private static array $_instances = [];

    /**
     * Extendable list of single upload processors
     * These are shared for all task-imports in a request
     * @var [editor_Models_Import_UploadProcessor_GenericUpload]
     */
    protected static $singleUploadProcessors = [];

    /**
     * Adds Upload-Processors
     * Plugins need to do that in the bootsrapping-phase
     */
    public static function addUploadProcessor(editor_Models_Import_UploadProcessor_GenericUpload $up)
    {
        self::$singleUploadProcessors[$up->getFieldName()] = $up;
    }

    /**
     * Retrieves the instance for the current task.
     */
    public static function taskInstance(editor_Models_Task $task): editor_Models_Import_UploadProcessor
    {
        $taskIdentifier = trim($task->getTaskGuid(), '{}');
        if (! array_key_exists($taskIdentifier, self::$_instances)) {
            self::$_instances[$taskIdentifier] = new self($task);
        }

        return self::$_instances[$taskIdentifier];
    }

    /**
     * Is set to true if there are some unknown upload errors (mostly POST size exceed ini size, which must be handled by the admin)
     * @var boolean
     */
    protected $hasUnknownErrors = false;

    /**
     * container for upload errors
     * @var array
     */
    protected $uploadErrors = [];

    /**
     * Zender Interface to uploaded files
     * @var Zend_File_Transfer_Adapter_Http
     */
    protected $upload;

    /**
     * Reference to the Imports main upload field
     * @var editor_Models_Import_UploadProcessor_ImportUpload
     */
    protected $mainUpload;

    private function __construct(editor_Models_Task $task)
    {
        $this->upload = new Zend_File_Transfer_Adapter_Http();

        //add the processor for the default mandatory import file:
        $this->mainUpload = ZfExtended_Factory::get(editor_Models_Import_UploadProcessor_ImportUpload::class, [$task]);
        self::addUploadProcessor($this->mainUpload);

        //add the optional TBX file
        self::addUploadProcessor(ZfExtended_Factory::get(editor_Models_Import_UploadProcessor_GenericUpload::class, [self::FIELD_TBX]));

        //add the optional Task Config File
        self::addUploadProcessor(ZfExtended_Factory::get(editor_Models_Import_UploadProcessor_GenericUpload::class, [self::FIELD_CONFIG]));

        //examples for adding relais and reference files to single upload too
        //$config = Zend_Registry::get('config')->runtimeOptions->import;
        //self::addUploadProcessor(ZfExtended_Factory::get('editor_Models_Import_UploadProcessor_GenericUpload', [ TO_BE_DEFINED, $config->relaisDirectory]));
    }

    /**
     * returns the main uploadfield processor
     */
    public function getMainUpload(): editor_Models_Import_UploadProcessor_GenericUpload
    {
        return $this->mainUpload;
    }

    /**
     * checks the given upload data
     */
    public function initAndValidate()
    {
        foreach (self::$singleUploadProcessors as $up) {
            /* @var $up editor_Models_Import_UploadProcessor_GenericUpload */
            $up->initAndValidate($this->upload, function (array $errors) use ($up) {
                foreach ($errors as $type => $error) {
                    $this->addUploadError($up->getFieldName(), $type, $error);
                }
            });
        }
        //if we want to port extra data for logging into the throw method, then we have to throw after each error, since we can only pass errors for one file.
        $this->throwOnUploadError();
    }

    /**
     * returns the uploaded file arrays per upload identifier
     */
    public function getFiles(): array
    {
        $result = [];
        foreach (self::$singleUploadProcessors as $up) {
            /* @var $up editor_Models_Import_UploadProcessor_GenericUpload */
            $result[$up->getFieldName()] = $up->getFiles();
        }

        return $result;
    }

    /**
     * returns the target directories per upload identifier
     *
     * Hint: From the view point of architecture this information does not belong to the uploadProcessor but to the DataProvider.
     * But doing it this way we have only one hook here, and no additional hook for plug-ins in the SingleUploader is needed
     */
    public function getTargetDirectories(): array
    {
        $result = [];
        foreach (self::$singleUploadProcessors as $up) {
            /* @var $up editor_Models_Import_UploadProcessor_GenericUpload */
            $result[$up->getFieldName()] = $up->getTargetDirectory();
        }

        return $result;
    }

    /**
     * Adds an upload error
     * @see editor_Models_Import_UploadProcessor::throwOnUploadError
     */
    protected function addUploadError(string $fileField, string $errorType, string $msg = null)
    {
        if (! isset($this->uploadErrors[$fileField])) {
            $this->uploadErrors[$fileField] = [];
        }
        switch ($errorType) {
            case self::ERROR_INVALID_FILE:
                $msg = $msg ?? 'Der Dateityp "{ext}" der ausgewählten Datei "{filename}" wird nicht unterstützt.';

                break;
            case self::ERROR_EMPTY_FILE:
                $msg = $msg ?? 'Die ausgewählte Datei war leer!';

                break;
            case self::ERROR_NO_WORKFILES:
                $msg = 'Es wurden keine hochgeladenen Arbeitsdateien gefunden oder die hochgeladenen Dateien überschreiten die maximal zulässige Größe.';

                break;
            default:
                $this->hasUnknownErrors = true;
                $msg = $msg ?? $errorType;

                break;
        }
        $this->uploadErrors[$fileField][$errorType] = $msg;
    }

    /**
     * throws upload errors if some occured
     * @throws ZfExtended_FileUploadException
     */
    protected function throwOnUploadError(array $extraData = [])
    {
        if (empty($this->uploadErrors)) {
            return;
        }
        $e = ZfExtended_FileUploadException::createResponse('E1026', $this->uploadErrors, $extraData);
        if ($this->hasUnknownErrors) {
            //in this case the admin must be informed
            Zend_Registry::get('logger')->exception($e, [
                'level' => ZfExtended_Logger::LEVEL_ERROR,
            ]);
        }

        throw $e;
    }
}
