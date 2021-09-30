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
 */
/**
 */
class editor_Plugins_ArchiveTaskBeforeDelete_Archiver_DataDirectory implements editor_Plugins_ArchiveTaskBeforeDelete_Archiver_Interface {
    use editor_Plugins_ArchiveTaskBeforeDelete_TLogger;
    
    protected $archiveZipName;
    
    /**
     * (non-PHPdoc)
     * @see editor_Plugins_ArchiveTaskBeforeDelete_Archiver_Interface::archive()
     */
    public function archive(string $targetDirectory, editor_Models_Task $task) {
        $taskRoot = $task->getAbsoluteTaskDataPath().DIRECTORY_SEPARATOR;
        $taskGuid = $task->getTaskGuid();
        $archiveZipName = $targetDirectory.DIRECTORY_SEPARATOR.trim($taskGuid, '{}; ').'.zip';
        if(file_exists($archiveZipName)){
            throw new ZfExtended_Exception('Archive Zip for Task '.$taskGuid.' does already exist!');
        }
        
        $tempnam = tempnam($targetDirectory, 'archiver_');
        $filter = ZfExtended_Factory::get('Zend_Filter_Compress',array(
            array(
                    'adapter' => 'Zip',
                    'options' => array(
                        'archive' => $tempnam
                    ),
                )
            )
        );
        
        $this->addReadme($taskRoot);
        
        if(!$filter->filter($taskRoot)){
            unlink($tempnam);
            throw new ZfExtended_Exception('Could not create export-zip of task '.$taskGuid.'.');
        }
        
        //ensure that a complete zip file is created, even with race conditions
        rename($tempnam, $archiveZipName);
        
        $this->log('Zip Archive created for Task '.$taskGuid.': '.$archiveZipName);
        $this->check($taskRoot, $taskGuid, $archiveZipName);
        
        return $archiveZipName;
    }
    
    /**
     * Adds a readme file with the confluence link how to reimport the created archive
     * @param string $taskRoot
     */
    protected function addReadme($taskRoot) {
        file_put_contents($taskRoot.'/readme.txt', 'For reimport / usage of this archive in translate5 see http://confluence.translate5.net/display/TPLO/ArchiveTaskBeforeDelete'."\n");
    }
    
    /**
     * checks the ZIP integrity and the stored filenames against filenames in filesystem
     * @param string $taskRoot
     * @param string $taskGuid
     * @param string $archiveZipName
     * @throws ZfExtended_Exception
     */
    protected function check($taskRoot, $taskGuid, $archiveZipName) {
        $zip = new ZipArchive();
        //checking ZIP File itself:
        $res = $zip->open($archiveZipName, ZipArchive::CHECKCONS);
        if($res !== true) {
            $msg = 'Could not validate archive zip for task '.$taskGuid.' with Zip error: '.$this->getZipError($res).'.';
            throw new ZfExtended_Exception($msg);
        }
    
        //compare containing file names against filenames in filesystem:
        $files = array();
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $files[] = $zip->getNameIndex($i);
        }
        
        $objects = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($taskRoot, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST
        );
        $toCompare = array();
        $config = Zend_Registry::get('config');
        $taskDirRoot = $config->runtimeOptions->dir->taskData.DIRECTORY_SEPARATOR;
        foreach($objects as $name => $object){
            if($object->isDir()) {
                $name .= DIRECTORY_SEPARATOR;
            }
            $toCompare[] = str_replace($taskDirRoot, '', $name);
        }
        $toCompare[] = str_replace($taskDirRoot, '', $taskRoot); //add root directory itself, this is not done by iterator
        
        $zipAdditional = array_diff($files, $toCompare);
        $fsAdditional = array_diff($toCompare, $files);
        if(!empty($fsAdditional)) {
            throw new ZfExtended_Exception('Archive zip does not contain all files (Task '.$taskGuid.'):'.print_r($fsAdditional, 1));
        }
        if(!empty($zipAdditional)) {
            throw new ZfExtended_Exception('Archive zip does contain additional files (Task '.$taskGuid.'):'.print_r($zipAdditional, 1));
        }
    }
    
    /**
     */
    protected function getZipError($errorCode) {
        switch($errorCode) {
            case ZipArchive::ER_EXISTS:
                return 'File already exists.';
            case ZipArchive::ER_INCONS:
                return 'Zip archive inconsistent.';
            case ZipArchive::ER_INVAL:
                return 'Invalid argument.';
            case ZipArchive::ER_MEMORY:
                return 'Malloc failure.';
            case ZipArchive::ER_NOENT:
                return 'No such file.';
            case ZipArchive::ER_NOZIP:
                return 'Not a zip archive.';
            case ZipArchive::ER_OPEN:
                return 'Can\'t open file.';
            case ZipArchive::ER_READ:
                return 'Read error.';
            case ZipArchive::ER_SEEK:
                return 'Seek error.';
            case ZipArchive::ER_CRC:
                return 'Checksum failed';
            default:
                $refl = new ReflectionClass('ZipArchive');
                $zipConsts = $refl->getConstants();
                foreach($zipConsts as $const => $value) {
                    if(strpos($const, 'ER_') === 0 && $value === $errorCode) {
                        return 'Unknown: '.$const;
                    }
                }
        }
        return 'Unknown: '.$errorCode;
    }
}