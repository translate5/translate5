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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * FileManager Test Plugin
 */
class editor_Plugins_Okapi_Tikal_Connector {
    
    protected $executable;
    
    /**
     * @var editor_Models_Task
     */
    protected $task;
    
    /**
     * @var editor_Models_Import_Configuration
     */
    protected $importConfig;
    
    /**
     * @var Zend_Config
     */
    protected $config;
    
    public function __construct(editor_Models_Task $task, editor_Models_Import_Configuration $importConfig = null) {
        $this->config = Zend_Registry::get('config');
        $this->task = $task;
        $this->importConfig = $importConfig;
        $this->executable = $this->config->runtimeOptions->plugins->Okapi->tikal->executable;
        if(empty($this->executable)) {
            throw new ZfExtended_Exception('Okapi tikal error: no path to the tikal executable was configured! Please set runtimeOptions.plugins.Okapi.tikal.executable');
        }
        if(!is_executable($this->executable)) {
            throw new ZfExtended_Exception('Okapi tikal error: configured tikal executable is not executable! path: '.$this->executable);
        }
    }
    
    /**
     * Starts tikal to extract the content from the given file to a XLF file
     * @param unknown $file
     * @return boolean
     */
    public function extract($file) {
        //FIXME Import tut, aber da die ref files nicht korrekt abgelegt werden (sind ja bereits verarbeitet) tut der export nicht 
        // von Tasks die auf diesem Wege importiert wurden. 
        $tikalDir = $this->task->getAbsoluteTaskDataPath().'/okapi-tikal/';
        mkdir($tikalDir);
        $tikalFile = $tikalDir.basename($file);
        copy($file, $tikalFile);
        exec($this->makeCmd($tikalFile), $output, $result);
        $res = $result === 0;
        if(!$res) {
            error_log('Okapi Tikal error: could not convert file '.$file.' message was: '.print_r($output, 1));
            return false;
        }
        $xlf = $tikalFile.'.xlf';
        if(!file_exists($xlf)) {
            error_log('Okapi Tikal error: Could not create a XLF file! Expected file '.$xlf);
            return false;
        }
        if(file_exists($file.'.xlf')){
            error_log('Okapi Tikal error: target XLF file does already exist in import package and would not be overwritten! File: '.$xlf);
            return false;
        }
        //move the generated XLF file into the proofRead folder
        rename($xlf, $file.'.xlf');
        $refFile = str_replace($this->importConfig->getProofReadDir(), $this->importConfig->getReferenceFilesDir(), $file);
        @mkdir(dirname($refFile), 0777, true);
        //move original file to the reference file folder
        rename($file, $refFile);
        unlink($tikalFile);
        return true;
    }
    
    /**
     * Works currently only with the longhorn import! Not with the above Tikal Import! 
     * 
     * merges back the XLF content into the original files
     * @param string $path
     * @return boolean
     */
    public function merge($path) {
        $origPath = substr($path, 0, -4);
        
        $taskPath = $this->task->getAbsoluteTaskDataPath();
        $refDirConf = $this->config->runtimeOptions->import->referenceDirectory;
        $refDir = $taskPath.DIRECTORY_SEPARATOR.$refDirConf;
        
        //remove absPrefix path:
        $relPath = str_replace($taskPath, '', $origPath);
        
        //replace taskGuid (the export dir) with refDir
        $refPath = str_replace($this->task->getTaskGuid(), $refDirConf, $relPath);
        
        copy($taskPath.$refPath, $origPath);
        
        exec($this->makeCmd($path, false), $output, $result);
        $res = $result === 0;
        if(!$res) {
            error_log('Okapi Tikal error: could not merge file '.$path.' message was: '.print_r($output, 1));
            return false;
        }
        return true;
        //code needed if trying to merge files extracted nativly with tikal 
        if(substr($path, -4) != '.xlf') {
            //file was created with the tikal import filter and filename from the DB does not contain XLF suffix then, so lets rename it
            rename($path, $path.'.xlf');
        }
    }
    
    /**
     * @param unknown $filepath
     * @param string $import
     * @return unknown
     */
    protected function makeCmd($filepath, $import = true) {
        $cmd = array(escapeshellarg($this->executable));
        $cmd[] = $import ? '-x1' : '-m1';
        $cmd[] = escapeshellarg($filepath);
        //if($addFileParam) {
            //$cmd[] = '< %s';
        //}
        //$cmd[] = '2>&1';
        return join(' ', $cmd);
    }
}