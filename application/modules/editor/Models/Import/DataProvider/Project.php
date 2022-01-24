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

    /***
     * The uploaded files
     * @var array
     */
    protected array $files = [];
    /***
     * Languages of the uploaded files.
     * @var array
     */
    protected array $fileLanguages = [];

    /***
     * Uploaded file types
     * @var array
     */
    protected array $fileTypes = [];

    /***
     * @var editor_Models_Import_SupportedFileTypes|mixed
     */
    protected editor_Models_Import_SupportedFileTypes $supportedFiles;

    public function __construct(array $files, array $langauges, array $types){
        $this->files = $files;
        $this->fileLanguages = $langauges;
        $this->fileTypes = $types;
        $this->supportedFiles = ZfExtended_Factory::get('editor_Models_Import_SupportedFileTypes');
    }

    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_DataProvider_Abstract::checkAndPrepare()
     */
    public function checkAndPrepare(editor_Models_Task $task) {
        $this->setTask($task);
        $this->checkAndMakeTempImportFolder();
        // if the current task is project, no need to move files
        if(!$task->isProject()){
            $this->handleUploads();
        }
        if(!is_dir($this->importFolder)){
            //DataProvider Directory: The importRootFolder "{importRoot}" does not exist!
            throw new editor_Models_Import_DataProvider_Exception('E1248', [
                'task' => $this->task,
                'importRoot' => $this->importFolder,
            ]);
        }
    }

    /**
     * @throws ZfExtended_ErrorCodeException
     * @throws editor_Models_Import_DataProvider_Exception
     */
    public function handleUploads() {

        $this->createTaskTempDir();

        $importFilesValues = array_values($this->files['importUpload']);
        $importFilesKeys = array_keys($this->files['importUpload']);

        $matchingFiles = [];
        $matchingFilesTypes = [];


        // find all matching non pivot files
        for($i=0;$i<count($this->fileLanguages);$i++){
            if($this->isWorkfileFileMatch($i)){
                $matchingFiles[$importFilesKeys[$i]] = $importFilesValues[$i];
                // collect the matching type for the file
                $matchingFilesTypes[$importFilesKeys[$i]] = $this->fileTypes[$i];
            }
        }

        $pivotFiles = [];
        // find all matching pivot files
        for($i=0;$i<count($importFilesValues);$i++){
            if($this->isPivotFileMatch($i)){
                foreach($matchingFiles as $fileName) {
                    if($this->filesMatch($fileName,$importFilesValues[$i])){
                        // check if the pivot file name matches one of the matching work-file name
                        $pivotFiles[$importFilesKeys[$i]] = $importFilesValues[$i];
                        // collect the matching type for the file
                        $matchingFilesTypes[$importFilesKeys[$i]] = $this->fileTypes[$i];
                    }
                }
            }
        }
        // merge the other files with the pivot files
        $matchingFiles = array_merge($matchingFiles,$pivotFiles);

        if(empty($matchingFiles)){
            throw new editor_Models_Import_DataProvider_Exception('E1369', [
                'task' => $this->task,
                'files' => $this->files,
                'fileLanguages' => $this->fileLanguages,
                'fileTypes' => $this->fileTypes,
            ]);
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

        // if the current task does not have relais files, set the relais language of this task to 0
        if(!in_array('pivot',$matchingFilesTypes)){
            $this->task->setRelaisLang(0);
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

    /***
     * Is the file-type workfile at given index
     * @param int $arrayIndex
     * @return bool
     */
    protected function isWorkFile(int $arrayIndex): bool
    {
        return $this->fileTypes[$arrayIndex] === 'workfiles';
    }

    /***
     * Is the file-type pivot at given index
     * @param int $arrayIndex
     * @return bool
     */
    protected function isPivotFile(int $arrayIndex): bool
    {
        return $this->fileTypes[$arrayIndex] === 'pivot';
    }

    /***
     * Check if the language at the task index matches the current task target language.
     * The match is true also when the language is empty at that index (non-bilingual file)
     * @param int $arrayIndex
     * @return bool
     */
    protected function isWorkfileFileMatch(int $arrayIndex): bool
    {
        return $this->isWorkFile($arrayIndex) && $this->fileLanguages[$arrayIndex] === $this->task->getTargetLang() || empty($this->fileLanguages[$arrayIndex]);
    }

    /***
     * Check if for the given index,there is pivot file match
     * @param int $arrayIndex
     * @return bool
     */
    protected function isPivotFileMatch(int $arrayIndex): bool
    {
        return $this->isPivotFile($arrayIndex) && $this->fileLanguages[$arrayIndex] === $this->task->getRelaisLang();
    }

    /***
     * Check if the workFile and the pivotFiles names are the same.
     * If the workFile uses okapi parser, we add .xlf as additional extension, since this will be the final
     * filename which will be matched for pivot patch
     * @param string $workFile
     * @param string $pivotFile
     * @return bool
     */
    protected function filesMatch(string $workFile, string $pivotFile): bool
    {
        if($workFile === $pivotFile){
            return true;
        }
        $ext = pathinfo($workFile,PATHINFO_EXTENSION);
        // if the workFile has native parser and the files are not matched by name then those files have different name
        if($this->supportedFiles->hasParser($ext)){
            return false;
        }
        // if the extension is supported, this file will be processed by okapi
        $supported = in_array($ext,$this->supportedFiles->getSupportedExtensions());
        return $supported && ($workFile.'.xlf' === $pivotFile);
    }
}
