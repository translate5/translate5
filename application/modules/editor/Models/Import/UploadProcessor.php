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

/**
 * provides helper methods for handling the import upload data
 * if there are new upload formats, a new dataprovider has to be added and this file has to be changed
 * if there are new import formats, this file has also to be changed 
 * 
 * @todo: remove the dependency between upload and fileformat, the uploader should only differ between single and zip!
 * currently we have to know the concrete type for TBX processing. if changing TBX import, we could also change this.
 */
class editor_Models_Import_UploadProcessor {
    const TYPE_ZIP = 'zip';
    
    /**
     * The fieldname for the generic field for TBX upload
     * @var string
     */
    const FIELD_TBX = 'importTbx';
    
    const ERROR_INVALID_FILE = 'noValidUploadFile';
    const ERROR_EMPTY_FILE = 'emptyUploadFile';
    
    /**
     * @deprecated remove in DEV branch, is done by DP factory then
     * @var editor_Models_Import_DataProvider_Abstract
     */
    protected $dataProvider;
    
    /**
     * container for upload errors
     * @var array
     */
    protected $uploadErrors = array();

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
    
    /**
     * Extendable list of single upload processors
     * @var [editor_Models_Import_UploadProcessor_GenericUpload]
     */
    protected static $singleUploadProcessors = [];
    
    public static function addUploadProcessor(editor_Models_Import_UploadProcessor_GenericUpload $up) {
        self::$singleUploadProcessors[$up->getFieldName()] = $up;
    }
    
    public function __construct() {
        $this->upload = new Zend_File_Transfer_Adapter_Http();
        
        //add the processor for the default mandatory import file:
        $this->mainUpload = ZfExtended_Factory::get('editor_Models_Import_UploadProcessor_ImportUpload');
        self::addUploadProcessor($this->mainUpload);
        
        //add the optional TBX file
        self::addUploadProcessor(ZfExtended_Factory::get('editor_Models_Import_UploadProcessor_GenericUpload', [self::FIELD_TBX]));
        
        //examples for adding relais and reference files to single upload too
        //$config = Zend_Registry::get('config')->runtimeOptions->import;
        //self::addUploadProcessor(ZfExtended_Factory::get('editor_Models_Import_UploadProcessor_GenericUpload', [ TO_BE_DEFINED, $config->relaisDirectory]));
        //self::addUploadProcessor(ZfExtended_Factory::get('editor_Models_Import_UploadProcessor_GenericUpload', [ TO_BE_DEFINED, $config->referenceDirectory]));
    }
    
    /**
     * checks the given upload data and inits matching the dataprovider
     */
    public function initAndValidate() {
        
        //TODO cloneAction fix, remove in DEV branch        
        if(!empty($this->dataProvider)) {
            //there is already set a data provider
            return;
        }

        foreach(self::$singleUploadProcessors as $up) {
            /* @var $up editor_Models_Import_UploadProcessor_GenericUpload */
            $up->initAndValidate($this->upload, function(array $errors) use ($up){
                foreach($errors as $error) {
                    $this->addUploadError($up->getFieldName(), $error);
                }
            });
        }
        //if we want to port extra data for logging into the throw method, then we have to throw after each error, since we can only pass errors for one file.
        $this->throwOnUploadError();
        
        //TODO rebuild in DEV branch, for DPfactory usage
        $this->initDataProvider($this->mainUpload->getFileExtension());
    }
    
    /**
     * Inits the dataprovider by copying the archivefile from an existing task.
     * @param editor_Models_Task $task
     */
    public function initFromTask(editor_Models_Task $task) {
        $oldTaskPath = new SplFileInfo($task->getAbsoluteTaskDataPath().'/'.editor_Models_Import_DataProvider_Abstract::TASK_ARCHIV_ZIP_NAME);
        if(!$oldTaskPath->isFile()){
            throw new ZfExtended_Exception('The task to be cloned does not have a import archive zip! Path: '.$oldTaskPath);
        }
        $copy = tempnam(sys_get_temp_dir(), 'taskclone');
        copy($oldTaskPath, $copy);
        $copy = new SplFileInfo($copy);
        ZfExtended_Utils::cleanZipPaths($copy, editor_Models_Import_DataProvider_Abstract::TASK_TEMP_IMPORT);
        $this->initByGivenZip($copy);
    }
    
    /**
     * returns the uploaded file arrays per upload identifier
     * @return array
     */
    protected function getFiles(): array {
        $result = [];
        foreach(self::$singleUploadProcessors as $up) {
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
     * @return array
     */
    protected function getTargetDirectories(): array {
        $result = [];
        foreach(self::$singleUploadProcessors as $up) {
            /* @var $up editor_Models_Import_UploadProcessor_GenericUpload */
            $result[$up->getFieldName()] = $up->getTargetDirectory();
        }
        return $result;
    }
    
    /**
     * Inits the dataprovider with the given ZIP file
     * @param SplFileInfo $pathToZip
     * TODO remove in DEV branch
     */
    public function initByGivenZip(SplFileInfo $zipFile) {
        $this->dataProvider = ZfExtended_Factory::get('editor_Models_Import_DataProvider_Zip', [$zipFile->getPathname()]);
    }
    
    /**
     * @param string $type
     * @param array $importInfo
     */
    public function initDataProvider($type) {
        if($type === self::TYPE_ZIP) {
            $dp = 'editor_Models_Import_DataProvider_Zip';
            $tmpfiles = array_keys($this->mainUpload->getFiles());
            $args = [reset($tmpfiles)]; //first uploaded review file is used as ZIP file
        } else {
            $dp = 'editor_Models_Import_DataProvider_SingleUploads';
            $args = [
                $this->getFiles(),
                $this->getTargetDirectories(),
            ];
        }
        $this->dataProvider = ZfExtended_Factory::get($dp, $args);
    }

    /**
     * returns the configured dataprovider
     * @return editor_Models_Import_DataProvider_Abstract
     */
    public function getDataProvider() {
        return $this->dataProvider;
    }
    
    /**
     * Adds an upload error
     * @see editor_Models_Import_UploadProcessor::throwOnUploadError
     * @param string $errorType
     */
    protected function addUploadError(string $fileField, string $errorType, string $msg = null) {
        if(!isset($this->uploadErrors[$fileField])) {
            $this->uploadErrors[$fileField] = [];
        }
        switch ($errorType) {
            case self::ERROR_INVALID_FILE:
                $msg = $msg ?? 'Der Dateityp "{ext}" der ausgewählten Datei "{filename}" wird nicht unterstützt.';
                break;
            case self::ERROR_EMPTY_FILE:
                $msg = $msg ?? 'Die ausgewählte Datei war leer!';
                break;
            default:
                $msg = 'Unbekannter Fehler beim Dateiupload.';
                break;
        }
        $this->uploadErrors[$fileField][$errorType] = $msg;
    }

    /**
     * throws upload errors if some occured 
     * @throws ZfExtended_FileUploadException
     */
    protected function throwOnUploadError(array $extraData = []) {
        if(empty($this->uploadErrors)) {
            return;
        }
        throw ZfExtended_FileUploadException::createResponse('E1026', $this->uploadErrors, $extraData);
    }
    
}