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
 * Factory for the different DataProvider implementations
 */
class editor_Models_Import_DataProvider_Factory {

    /**
     * Create the dataprovider from the given task so that it can be cloned
     * @param editor_Models_Task $task
     * @throws ZfExtended_Exception
     * @return editor_Models_Import_DataProvider_Abstract
     */
    public function createFromTask(editor_Models_Task $task): editor_Models_Import_DataProvider_Abstract {
        $oldTaskPath = new SplFileInfo($task->getAbsoluteTaskDataPath().'/'.editor_Models_Import_DataProvider_Abstract::TASK_ARCHIV_ZIP_NAME);
        if(!$oldTaskPath->isFile()){
            throw new editor_Models_Import_DataProvider_Exception('E1265', [
                'task' => $task,
                'path' =>$oldTaskPath,
            ]);
        }

        $files = $this->filterFiles($task);
        $copy = tempnam(sys_get_temp_dir(), 'taskclone');

        foreach ($files as $from => $to) {
            $tmpTo = $copy.DIRECTORY_SEPARATOR.editor_Models_Import_DataProvider_Abstract::TASK_TEMP_IMPORT.DIRECTORY_SEPARATOR.'workfiles'.DIRECTORY_SEPARATOR.$to;
            copy($from, $tmpTo);
        }
        $copy = new SplFileInfo($copy);
        ZfExtended_Utils::cleanZipPaths($copy, editor_Models_Import_DataProvider_Abstract::TASK_TEMP_IMPORT);
        return ZfExtended_Factory::get('editor_Models_Import_DataProvider_Zip', [$copy->getPathname()]);
    }

    /**
     * Determines which UploadProcessor should be used for uploaded data, creates and returns it
     * @param editor_Models_Import_UploadProcessor $upload
     * @return editor_Models_Import_DataProvider_Abstract
     */
    public function createFromUpload(editor_Models_Import_UploadProcessor $upload): editor_Models_Import_DataProvider_Abstract {
        $mainUpload = $upload->getMainUpload();

        $files = $mainUpload->getFiles();
        $isZip = count($files) === 1 && $mainUpload->getFileExtension((array_values($files)[0])) === $upload::TYPE_ZIP;
        if($isZip) {
            $dp = 'editor_Models_Import_DataProvider_Zip';
            $tmpfiles = array_keys($mainUpload->getFiles());
            $args = [reset($tmpfiles)]; //first uploaded review file is used as ZIP file
        } else {
            $dp = 'editor_Models_Import_DataProvider_SingleUploads';
            $args = [
                $upload->getFiles(),
                $upload->getTargetDirectories(),
            ];
        }
        return ZfExtended_Factory::get($dp, $args);
    }

    public function filterFiles(editor_Models_Task $task){
        $path = $task->getAbsoluteTaskDataPath().DIRECTORY_SEPARATOR.editor_Models_Import_DataProvider_Abstract::TASK_TEMP_IMPORT;
        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
        $files = array();
        foreach ($rii as $file) {
            //TODO: filter out not supported filetypes. How do we handle reference files ?
            if ($file->isDir()){
                continue;
            }
            if(!$this->isBilingual($file->getFilename())){

                continue;
            }
            if($this->isBilingualMatch($file->getFilename(),$task->getTa())){
                $from = $file->getPathname();
                $to = $task->getTargetLang();
                $files[$from] = $to;
            }
        }
        return $files;
    }

    /***
     * Same check as in the frontend to detect bilingual data. Also the files filter should
     * @param string $file
     * @return bool
     */
    public function isBilingual(string $file){
        return true;
    }

    public function isBilingualMatch(string $file, string $sourceLanguage){
        return strpos($file,$sourceLanguage)!==false;
    }
}