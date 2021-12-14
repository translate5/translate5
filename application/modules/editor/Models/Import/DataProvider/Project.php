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

    public function handleUploads(array $files, array $languages,array $filetypes) {

        $this->createTaskTempDir();

        $importFilesValues = array_values($files['importUpload']);
        $importFilesKeys = array_keys($files['importUpload']);

        $matchingFiles = [];
        $matchingFilesTypes = [];

        for($i=0;$i<count($languages);$i++){
            // if the file language matches the task index or the file language at the index is empty (non-bilingual file)
            if($languages[$i] === $this->task->getTargetLang() || empty($languages[$i])){
                $matchingFiles[$importFilesKeys[$i]] = $importFilesValues[$i];
                // collect the matching type for the file
                $matchingFilesTypes[$importFilesKeys[$i]] = $filetypes[$i];
            }
        }
        if(empty($matchingFiles)){
            throw new ZfExtended_ErrorCodeException();
        }

        foreach($matchingFiles as $tmpFile => $fileName) {
            $name = $this->getFilepathByName($fileName,$matchingFilesTypes[$tmpFile]);
            if(!copy($tmpFile, $name)) {
                //DataProvider SingleUpload: Uploaded file "{file}" cannot be moved to "{target}',
                throw new editor_Models_Import_DataProvider_Exception('E1244', [
                    'task' => $this->task,
                    'file' => $fileName,
                    'target' => $name,
                ]);
            }
        }
    }

    /***
     * Archive the import package
     *
     * @param $filename
     * @return void
     * @throws editor_Models_Import_DataProvider_Exception
     */
    public function archiveImportedData($filename = null)
    {
        $filter = new Zend_Filter_Compress(array(
            'adapter' => 'Zip',
            'options' => array(
                'archive' => $this->getZipArchivePath($filename)
            ),
        ));
        if(!$filter->filter($this->importFolder)){
            //DataProvider Directory: Could not create archive-zip
            throw new editor_Models_Import_DataProvider_Exception('E1247', [
                'task' => $this->task,
            ]);
        }
    }

    /***
     * Create the temporary import folder for the current task
     * @return void
     * @throws editor_Models_Import_DataProvider_Exception
     */
    protected function createTaskTempDir(){
        $this->mkdir($this->getTaskTempDir());
    }

    /***
     * Get task temporary directory path
     * @return string
     */
    protected function getTaskTempDir(): string
    {
        return $this->importFolder.DIRECTORY_SEPARATOR;
    }

    /***
     * Return target directory for the given file type.
     * It can be: workfiles, relais and visualReview
     *
     * @param string $type
     * @return string
     */
    protected function getTargetDir(string $type): string
    {
        return match ($type) {
            'workfile' => editor_Models_Import_Configuration::WORK_FILES_DIRECTORY,
            'pivot' => editor_Models_Import_Configuration::RELAIS_FILES_DIRECTORY,
            default => $type,
        };
    }

    /***
     * Get the file path of the given file and file type.
     * If the file with the same name exist, incremental filename will be generated
     * @param string $fileName
     * @param string $fileType
     * @return string
     * @throws editor_Models_Import_DataProvider_Exception
     */
    protected function getFilepathByName(string $fileName,string $fileType): string
    {
        $target = $this->getTaskTempDir().$this->getTargetDir($fileType).DIRECTORY_SEPARATOR;
        $this->mkdir($target);

        $name = ZfExtended_Utils::addNumberIfExist($fileName,$target);
        return $target.$name;
    }
}
