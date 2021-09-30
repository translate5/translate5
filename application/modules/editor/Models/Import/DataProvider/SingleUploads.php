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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *

/**
 * Gets the Import Data from single uploaded files
 */
class editor_Models_Import_DataProvider_SingleUploads  extends editor_Models_Import_DataProvider_Directory {
    /**
     * @var array
     */
    protected $filesToProcess;
    protected $targetDirectories;

    /**
     * consumes all the given file paths
     * @param array $review
     * @param array $relais optional
     * @param array $reference optional
     * @param string $tbx optional
     */
    public function __construct(array $filesToProcess, array $targetDirectories){
        $this->filesToProcess = $filesToProcess;
        $this->targetDirectories = $targetDirectories;
    }

    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_DataProvider_Abstract::checkAndPrepare()
     */
    public function checkAndPrepare(editor_Models_Task $task){
        $this->setTask($task);
        $this->checkAndMakeTempImportFolder();
        parent::checkAndPrepare($task);

        foreach($this->filesToProcess as $type => $files) {
            if(empty($files)) {
                //if nothing was uploaded for a specific field, nothing can be done
                continue;
            }
            $this->handleUploads($files, $this->targetDirectories[$type] ?? null);
        }
    }

    /**
     * moves the given files to the desired folder
     * @param string $folder
     * @param array $files
     */
    protected function handleUploads(array $files, $folder = null) {
        $target = $this->importFolder.DIRECTORY_SEPARATOR;
        if(!empty($folder)) {
            $target .= $folder;
            $this->mkdir($target);
        }
        foreach($files as $tmpFile => $fileName) {
            $name = $target.DIRECTORY_SEPARATOR.$fileName;
            if(!move_uploaded_file($tmpFile, $name)) {
                //DataProvider SingleUpload: Uploaded file "{file}" cannot be moved to "{target}',
                throw new editor_Models_Import_DataProvider_Exception('E1244', [
                    'task' => $this->task,
                    'file' => $fileName,
                    'target' => $target,
                ]);
            }
        }
    }

    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_DataProvider_Abstract::postImportHandler()
     */
    public function postImportHandler() {
        $this->removeTempFolder();
    }

    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_DataProvider_Abstract::handleImportException()
     */
    public function handleImportException(Exception $e) {
        $this->removeTempFolder();
    }
}