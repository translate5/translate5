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
 * process a custom filefield in a generic way. The file is NOT mandatory, but if it does exist it is checked if it is valid
 */
class editor_Models_Import_UploadProcessor_GenericUpload {

    /**
     * The field name of the file upload
     * @var string
     */
    protected $fieldName;
    
    /**
     * Set to false if mandatory, for extending purposes
     * @var boolean
     */
    protected $optional = true;
    
    /**
     * FileInfo of the uploaded file (currently only one file per upload field)
     */
    protected $fileInfo = null;
    
    /**
     * The relative targetDirectory to be used in the importRoot
     * From the view point of architecture this information does not belong to the uploadProcessor but to the DataProvider. 
     * But doing it this way we have only one hook here, and no additional hook for plug-ins in the SingleUploader is needed 
     */
    protected $targetDirectory = null;
    
    /**
     * 
     * @param string $fieldName
     * @param string $targetDirectory optional, the targetDirectory in the importRoot where the files should be moved, defaults to null which means placing the file in the import root directly
     */
    public function __construct(string $fieldName, string $targetDirectory = null) {
        $this->fieldName = $fieldName;
        $this->targetDirectory = $targetDirectory;
    }
    
    /**
     * returns the fieldname
     * @return string
     */
    public function getFieldName(): string {
        return $this->fieldName;
    }
    
    /**
     * returns the fieldname
     * @return string
     */
    public function getTargetDirectory(): ?string {
        return $this->targetDirectory;
    }
    
    /**
     * checks the given upload data and inits matching the dataprovider
     * @return false if the basic validation failed
     */
    public function initAndValidate(Zend_File_Transfer_Adapter_Http $upload, Callable $addErrorCallback): bool {
        //due to the isUploaded check this file is not mandatory
        if($this->optional && !$upload->isUploaded($this->fieldName)) {
            return false; //stops processing, but since that is no error, no msg is stored
        }
        
        if(!$upload->isValid($this->fieldName)) {
            $addErrorCallback($upload->getMessages());
            return false;
        }

        $this->fileInfo = $upload->getFileInfo($this->fieldName);

        foreach ($this->fileInfo as $fileInfo){
            if(empty($fileInfo['size'])) {
                $addErrorCallback([editor_Models_Import_UploadProcessor::ERROR_EMPTY_FILE]);
                return false;
            }
        }

        return true;
    }

    /**
     * returns an array with tempnam => filename for all uploaded files of that field
     * @return array
     */
    public function getFiles(): array {
        if(empty($this->fileInfo)) {
            return [];
        }
        $files = [];
        foreach ($this->fileInfo as $fileInfo){
            $files[$fileInfo['tmp_name']] = $fileInfo['name'];
        }
        return $files;
    }
    
    /**+
     * Get the file extension of the given uploaded file name
     * @param string $fileName
     * @return string
     */
    public function getFileExtension(string $fileName): string {
        $importName = pathinfo($fileName);
        settype($importName['extension'], 'string');
        return strtolower($importName['extension']);
    }
}