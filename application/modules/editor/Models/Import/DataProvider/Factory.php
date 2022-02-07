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
        $copy = tempnam(sys_get_temp_dir(), 'taskclone');
        copy($oldTaskPath, $copy);
        $copy = new SplFileInfo($copy);
        ZfExtended_Utils::cleanZipPaths($copy, editor_Models_Import_DataProvider_Abstract::TASK_TEMP_IMPORT);
        return ZfExtended_Factory::get('editor_Models_Import_DataProvider_Zip', [$copy->getPathname()]);
    }

    /**
     * Determines which UploadProcessor should be used for uploaded data, creates and returns it
     * @param editor_Models_Import_UploadProcessor $upload
     * @return editor_Models_Import_DataProvider_Abstract
     */
    public function createFromUpload(editor_Models_Import_UploadProcessor $upload, array $data = []): editor_Models_Import_DataProvider_Abstract {
        $mainUpload = $upload->getMainUpload();

        $files = $mainUpload->getFiles();

        if($this->isZipUpload($upload)) {
            $dp = 'editor_Models_Import_DataProvider_Zip';
            $tmpfiles = array_keys($files);
            $args = [reset($tmpfiles)]; //first uploaded review file is used as ZIP file
        }else if($this->isProjectUpload($data)){
            $dp = 'editor_Models_Import_DataProvider_Project';
            $args = [
                $upload->getFiles(),
                $this->handleProjectLanguages($data[editor_Models_Import_DataProvider_Abstract::IMPORT_UPLOAD_LANGUAGES_NAME]),
                $data[editor_Models_Import_DataProvider_Abstract::IMPORT_UPLOAD_TYPE_NAME]
            ];
        } else {
            $dp = 'editor_Models_Import_DataProvider_SingleUploads';
            $args = [
                $upload->getFiles(),
                $upload->getTargetDirectories(),
            ];
        }
        return ZfExtended_Factory::get($dp, $args);
    }

    /***
     * For each langauge check and convert the given value to internal language id.
     * @param array $langauges
     * @return array
     */
    protected function handleProjectLanguages(array $langauges){
        foreach ($langauges as &$lang){
            $language = ZfExtended_Factory::get('editor_Models_Languages');
            /* @var $language editor_Models_Languages */
            $language->convertLanguage($lang);
        }
        return $langauges;
    }

    /***
     * Is the current upload zip package
     * @param editor_Models_Import_UploadProcessor $upload
     * @return bool
     */
    protected function isZipUpload(editor_Models_Import_UploadProcessor $upload): bool {
        $mainUpload = $upload->getMainUpload();
        $files = $mainUpload->getFiles();
        return count($files) === 1 && $mainUpload->getFileExtension((array_values($files)[0])) === $upload::TYPE_ZIP;
    }

    /***
     * Is the current upload project upload
     *
     * @param array $data
     * @return bool
     */
    protected function isProjectUpload(array $data): bool
    {
        return !empty($data[editor_Models_Import_DataProvider_Abstract::IMPORT_UPLOAD_LANGUAGES_NAME]);
    }
}