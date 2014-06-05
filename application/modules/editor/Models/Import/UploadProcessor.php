<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
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
    const TYPE_SDLXLIFF = 'sdlxliff';
    const TYPE_CSV = 'csv';
    
    /**
     * valid upload types, extension and mime combination.
     * The extension is also the internal used key.
     * @var array
     */
    protected $validUploadTypes = array(
        self::TYPE_ZIP => 'application/zip',
        self::TYPE_SDLXLIFF => 'application/xml',
        self::TYPE_CSV => 'text/plain',
    );
    
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
        
        //mandatory upload file
        $importInfo = $this->upload->getFileInfo('importUpload');
        
        $type = $this->checkAndGetImportType($importInfo);
        $this->initDataProvider($type, $importInfo);
    }
    
    /**
     * checks the uploaded data
     * @param array $importInfo
     * @return string
     */
    protected function checkAndGetImportType(array $importInfo) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        //mit oder ohne importUpload
        $importFile = $importInfo['importUpload']['tmp_name'];
        $importName = pathinfo($importInfo['importUpload']['name']);
        settype($importName['extension'], 'string');
        $ext = strtolower($importName['extension']);
        $mime = $finfo->file($importFile);
        
        if(isset($this->validUploadTypes[$ext]) && $mime == $this->validUploadTypes[$ext]) {
            return $ext; //extension is also the internal used type!
        }
        
        if(empty($importInfo['importUpload']['size'])) {
            $this->addUploadError('emptyUploadFile', $importInfo['importUpload']['name']);
        }
        else {
            $log = ZfExtended_Factory::get('ZfExtended_Log');
            /* @var $log ZfExtended_Log */
            $log->logError('Unknown mime type for extension "'.$ext.'" discovered',
                            'Someone tried the file extension "'.$ext.'" which should be the mime "'.$this->validUploadTypes[$ext].'" but we got the following mime: "'.$mime.'"');
            $this->addUploadError('noValidUploadFile', $importInfo['importUpload']['name']);
        }
        
        $this->throwOnUploadError();
    }
    
    /**
     * @param string $type
     * @param array $importInfo
     */
    protected function initDataProvider($type, $importInfo) {
        switch ($type) {
            case self::TYPE_ZIP:
                $dp = 'editor_Models_Import_DataProvider_Zip';
                $args = array($importInfo['importUpload']['tmp_name']);
            break;
            case self::TYPE_SDLXLIFF:
                $dp = 'editor_Models_Import_DataProvider_SingleUploads';
                $args = array(
                    array($importInfo['importUpload']), //proofReads
                );
                $args = $this->handleTbx($args);
            break;
            case self::TYPE_CSV:
                $dp = 'editor_Models_Import_DataProvider_SingleUploads';
                $args = array(
                    array($importInfo['importUpload']), //proofReads
                );
            break;
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
     * @see throwOnUploadError
     * @param string $errorType
     */
    protected function addUploadError($errorType) {
        $msgs = array(
            'noValidUploadFile' => 'Bitte eine ZIP, SDLXLIFF oder CSV Datei auswählen.',
            'emptyUploadFile' => 'Die ausgewählte Datei war leer!',
        );
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        /* @var $translate ZfExtended_Zendoverwrites_Translate */;
        if(empty($msgs[$errorType])) {
            $msg = $translate->_('Unbekannter Fehler beim Dateiupload.');
        }
        else {
            $msg = $translate->_($msgs[$errorType]);
        }
        $args = func_get_args();
        array_shift($args); //remove type
        array_unshift($args, $msg); //add formatted string as first parameter
        $this->uploadErrors[$errorType] = call_user_func_array('sprintf', $args);
    }

    /**
     * throws upload errors if some occured 
     * @throws ZfExtended_ValidateException
     */
    protected function throwOnUploadError() {
        if(empty($this->uploadErrors)) {
            return;
        }
        $errors = array('importUpload' => $this->uploadErrors);
        $e = new ZfExtended_ValidateException(print_r($errors, 1));
        $e->setErrors($errors);
        throw $e;
    }
    
}