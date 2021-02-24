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
 * process the importUpload field
 */
class editor_Models_Import_UploadProcessor_ImportUpload extends editor_Models_Import_UploadProcessor_GenericUpload {
    /**
     * @var string
     */
    protected $fieldName = 'importUpload';
    
    protected $optional = false;
    
    public function __construct() {
        $this->targetDirectory = editor_Models_Import_Configuration::getWorkfilesDirectoryName();
    }
    
    /**
     * checks the given upload data and inits matching the dataprovider
     * @return boolean
     */
    public function initAndValidate(Zend_File_Transfer_Adapter_Http $upload, Callable $addErrorCallback): bool {
        if(!parent::initAndValidate($upload, $addErrorCallback)) {
            return false;
        }
        
        $supportedFiles = ZfExtended_Factory::get('editor_Models_Import_SupportedFileTypes');
        /* @var $supportedFiles editor_Models_Import_SupportedFileTypes */
        $allValidExtensions = $supportedFiles->getSupportedExtensions();
        
        $ext = $this->getFileExtension();
        if(in_array($ext, $allValidExtensions)){
            return true;
        }
        
        $data = [
            'ext' => $ext,
            'filename' => $this->fileInfo['name'],
        ];
        
        $log = Zend_Registry::get('logger');
        /* @var $log ZfExtended_Logger */
        $log->info('E1031', 'A file "{filename}" with an unknown file extension "{ext}" was tried to be imported.', $data);
        $addErrorCallback([editor_Models_Import_UploadProcessor::ERROR_INVALID_FILE]);
        return false;
    }
}