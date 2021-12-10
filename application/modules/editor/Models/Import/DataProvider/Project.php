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

/***
 *
 */
class editor_Models_Import_DataProvider_Project  extends editor_Models_Import_DataProvider_Abstract {

    public function __construct(editor_Models_Task $task){
        $this->setTask($task);
        $this->checkAndMakeTempImportFolder();
    }

    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_DataProvider_Abstract::checkAndPrepare()
     */
    public function checkAndPrepare(editor_Models_Task $task) {
        $this->setTask($task);
        if(!is_dir($this->importFolder)){
            //DataProvider Directory: The importRootFolder "{importRoot}" does not exist!
            throw new editor_Models_Import_DataProvider_Exception('E1248', [
                'task' => $this->task,
                'importRoot' => $this->importFolder,
            ]);
        }
    }

    public function handleUploads(array $files, array $languages) {

        $target = $this->getTaskWorkfilesDir();

        $this->mkdir($target);

        $importFilesValues = array_values($files['importUpload']);
        $importFilesKeys = array_keys($files['importUpload']);

        $matchingFiles = [];

        for($i=0;$i<count($languages);$i++){
            // if the file language matches the task index or the file language at the index is empty (non-bilingual file)
            if($languages[$i] === $this->task->getTargetLang() || empty($languages[$i])){
                $matchingFiles[$importFilesKeys[$i]] = $importFilesValues[$i];
            }
        }
        if(empty($matchingFiles)){
            throw new ZfExtended_ErrorCodeException();
        }

        foreach($matchingFiles as $tmpFile => $fileName) {
            $name = $this->getFilepathByName($fileName);
            if(!copy($tmpFile, $name)) {
                //DataProvider SingleUpload: Uploaded file "{file}" cannot be moved to "{target}',
                throw new editor_Models_Import_DataProvider_Exception('E1244', [
                    'task' => $this->task,
                    'file' => $fileName,
                    'target' => $target,
                ]);
            }
        }
    }

    public function archiveImportedData($filename = null)
    {
        // TODO: Implement archiveImportedData() method.
    }

    /***
     * Get the task import workfiles directory
     * @return string
     */
    protected function getTaskWorkfilesDir(){
        return $this->importFolder.DIRECTORY_SEPARATOR.editor_Models_Import_Configuration::WORK_FILES_DIRECTORY.DIRECTORY_SEPARATOR;
    }

    /***
     * Get the file path of a given file in the task workfiles directory.
     * If the file already exist, the new file will be put in sub folder
     * @param string $fileName
     * @return string
     * @throws editor_Models_Import_DataProvider_Exception
     */
    protected function getFilepathByName(string $fileName){
        $target = $this->getTaskWorkfilesDir();

        $name = $target.$fileName;
        // if there is same named file, put it into a folder
        if(is_file($name)){
            $name = $target.pathinfo($fileName, PATHINFO_FILENAME);
            $this->mkdir($name);
            $name = $name.DIRECTORY_SEPARATOR.$fileName;
        }
        return $name;
    }
}
