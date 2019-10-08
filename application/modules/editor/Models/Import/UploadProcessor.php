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
    const TYPE_TESTCASE = 'testcase';
    
    /**
     * @var editor_Models_Import_DataProvider_Abstract
     */
    protected $dataProvider;
    
    /**
     * container for upload errors
     * @var array
     */
    protected $uploadErrors = array();

    /**
     * @var Zend_File_Transfer_Adapter_Http
     */
    protected $upload;
    
    public function __construct() {
        $this->upload = new Zend_File_Transfer_Adapter_Http();
    }
    
    /**
     * checks the given upload data and inits matching the dataprovider
     */
    public function initAndValidate() {
        if(!empty($this->dataProvider)) {
            //there is already set a data provider
            return;
        }
        
        if(!$this->upload->isValid('importUpload')) {
            $this->uploadErrors = $this->upload->getMessages();
            $this->throwOnUploadError();
        }
        
        //mandatory upload file
        $importInfo = $this->upload->getFileInfo('importUpload');
        
        $type = $this->checkAndGetImportType($importInfo);
        $this->initDataProvider($type, $importInfo);
    }
    
    /**
     * Inits the dataprovider with the given ZIP file
     * @param SplFileInfo $pathToZip
     */
    public function initByGivenZip(SplFileInfo $zipFile) {
        $this->dataProvider = ZfExtended_Factory::get('editor_Models_Import_DataProvider_Zip', [$zipFile->getPathname()]);
    }
    
    /**
     * checks the uploaded data
     * @param array $importInfo
     * @return string
     */
    protected function checkAndGetImportType(array $importInfo) {
        $importName = pathinfo($importInfo['importUpload']['name']);
        settype($importName['extension'], 'string');
        $ext = strtolower($importName['extension']);

        $supportedFiles = ZfExtended_Factory::get('editor_Models_Import_SupportedFileTypes');
        /* @var $supportedFiles editor_Models_Import_SupportedFileTypes */
        $allValidExtensions = $supportedFiles->getSupportedExtensions();
        
        $isEmptySize = empty($importInfo['importUpload']['size']);
        if(!$isEmptySize && in_array($ext, $allValidExtensions)){
            return $ext;
        }
        
        $data = [
            'ext' => $ext,
            'filename' => $importInfo['importUpload']['name'],
        ];
        if($isEmptySize) {
            $this->addUploadError('emptyUploadFile');
        }
        else {
            $log = Zend_Registry::get('logger');
            /* @var $log ZfExtended_Logger */
            $log->info('E1031', 'A file "{filename}" with an unknown file extension "{ext}" was tried to be imported.', $data);
            $this->addUploadError('noValidUploadFile');
        }
        $this->throwOnUploadError($data);
    }
    
    /**
     * @param string $type
     * @param array $importInfo
     */
    public function initDataProvider($type, $importInfo) {
        switch ($type) {
            case self::TYPE_ZIP:
                $dp = 'editor_Models_Import_DataProvider_Zip';
                $args = array($importInfo['importUpload']['tmp_name']);
            break;
            case self::TYPE_TESTCASE:
                $dp = 'editor_Models_Import_DataProvider_SingleUploads';
                $args = array(
                    array($importInfo['importUpload']), //proofReads
                );
                $args = $this->handleTbx($args);
                try {
                   $offlineTestcase = Zend_Registry::get('offlineTestcase');
                } catch (Exception $exc) {
                    $offlineTestcase = false;
                }
                if($offlineTestcase===true){
                    $args = array(
                        array($importInfo['importUpload']), //proofReads
                        array(), // relais files
                        array(), // reference files
                        $importInfo['importTbx'], //tbx
                    );
                }
                else{
                    $args = $this->handleSingleUpload($importInfo);
                }
            break;
            default:
                $dp = 'editor_Models_Import_DataProvider_SingleUploads';
                $args = $this->handleSingleUpload($importInfo);
            break;
        }
        $this->dataProvider = ZfExtended_Factory::get($dp, $args);
    }
    
    protected function handleSingleUpload($importInfo) {
        $dp = 'editor_Models_Import_DataProvider_SingleUploads';
        $args = array(
            array($importInfo['importUpload']), //proofReads
        );
        return $this->handleTbx($args);
    }
    /**
     * returns the configured dataprovider
     * @return editor_Models_Import_DataProvider_Abstract
     */
    public function getDataProvider() {
        return $this->dataProvider;
    }
    
    /**
     * handles the single uploaded TBX file, if needed
     * @param array $args
     * @return array returns the changed arguments
     */
    protected function handleTbx($args) {
        if(!$this->upload->isValid('importTbx')){
            return $args;
        }
        $args[] = array(); //currently no relais files
        $args[] = array(); //currently no reference files
        $tbx = $this->upload->getFileInfo('importTbx');
        $args[] = $tbx['importTbx']; //since tbx is a single file, we can provide only this file
        return $args;
    }
    
     /**
     * Adds an upload error
     * @see editor_Models_Import_UploadProcessor::throwOnUploadError
     * @param string $errorType
     */
    protected function addUploadError($errorType) {
        switch ($errorType) {
            case 'noValidUploadFile':
                $this->uploadErrors[$errorType] = 'Der Dateityp "{ext}" der ausgewählten Datei "{filename}" wird nicht unterstützt.';
                return;
            case 'emptyUploadFile':
                $this->uploadErrors[$errorType] = 'Die ausgewählte Datei war leer!';
                return;
            default:
                $this->uploadErrors[$errorType] = 'Unbekannter Fehler beim Dateiupload.';
                return;
        }
    }

    /**
     * throws upload errors if some occured 
     * @throws ZfExtended_FileUploadException
     */
    protected function throwOnUploadError(array $extraData = []) {
        if(empty($this->uploadErrors)) {
            return;
        }
        throw ZfExtended_FileUploadException::createResponse('E1026', ['importUpload' => $this->uploadErrors], $extraData);
    }
    
}