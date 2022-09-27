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
class editor_Plugins_ArchiveTaskBeforeDelete_Archiver_Database implements editor_Plugins_ArchiveTaskBeforeDelete_Archiver_Interface {
    use editor_Plugins_ArchiveTaskBeforeDelete_TLogger;

    /**
     * default executable of the mysql command, can be overwritten by config
     * @var string
     */
    const MYSQL_BIN = '/usr/bin/mysql';
    const DIR_SQL_DATA = 'db_data';
    const DIR_SQL_STRUCTURE = 'db_structure';
    
    /**
     * @param string $targetDirectory
     * @param editor_Models_Task $task
     */
    public function archive(string $targetDirectory, editor_Models_Task $task) {
        $tables = ZfExtended_Factory::get('editor_Plugins_ArchiveTaskBeforeDelete_DbTables');
        /* @var $tables editor_Plugins_ArchiveTaskBeforeDelete_DbTables */
        $tableList = $tables->getArchiveListFor($task->getTaskGuid());
        foreach ($tableList as $table => $params) {
            if(!$this->dumpDataAndStructure($targetDirectory, $table, $params, $task->getTaskGuid())) {
                throw new ZfExtended_Exception('While archiving task '.$task->getTaskGuid().' the following table could not be dumped: '.$table);
            }
        }
    }
    
    /**
     * dumps the given table with the given export params to the archive directory
     * @param string $targetDirectory
     * @param string $table
     * @param string $params
     * @param string $taskGuid
     * @return boolean
     */
    protected function dumpDataAndStructure($targetDirectory, $table, $params, $taskGuid) {
        $ds = DIRECTORY_SEPARATOR;
        $this->ensureSqlDirectoriesExist($targetDirectory);
        $output = null;
        $config = Zend_Registry::get('config');
        $db = $config->resources->db->params;
        
        $fileData = $targetDirectory.$ds.self::DIR_SQL_DATA.$ds.$table.'.sql';
        $callData = sprintf($this->makeSqlCmd($db, true), $this->prepareArgs($params), $this->prepareArgs($table), $this->prepareArgs($fileData));
        exec($callData, $output, $resultData);
        $this->log('Calling '.$callData.($resultData === 0 ? ' with no errors.':' results in '.file_get_contents($fileData)));
        $this->fixTaskState($taskGuid, $table, $fileData);
        
        $fileStructure = $targetDirectory.$ds.self::DIR_SQL_STRUCTURE.$ds.$table.'.sql'; 
        $callStructure = sprintf($this->makeSqlCmd($db, false), '', $this->prepareArgs($table), $this->prepareArgs($fileStructure));
        exec($callStructure, $output, $result);
        $this->log('Calling '.$callStructure.($result === 0 ? ' with no errors.':' results in '.file_get_contents($fileStructure)));
        
        return $result === 0 && $resultData === 0;
    }

    /**
     * adds an update statement to the LEK_task table to fix the task state
     * @param string $taskGuid
     * @param string $table
     * @param string $file
     */
    protected function fixTaskState($taskGuid, $table, $file) {
        if($table !== 'LEK_task') {
            return;
        }
        $taskData = file_get_contents($file);
        $result = preg_replace('/^UNLOCK TABLES;$/m', "UPDATE `LEK_task` set state = 'open', locked = null WHERE taskGuid = '".$taskGuid."';\nUNLOCK TABLES;", $taskData);
        file_put_contents($file, $result);
    }
    
    /**
     * Does a escapeshellarg for strings and arrays, later one are concatenated
     * @param mixed $params
     * @return string
     */
    protected function prepareArgs($params) {
        //empty parameters are mentioned to be ignored completly on commandline
        //passing null to escapeshellarg will result in an empty parameter,
        //which triggers mysql_dump to dump always the first table of the database in addition to the desired table!
        if(empty($params)){
            return '';
        }
        if(is_array($params)) {
            return join(' ', array_map('escapeshellarg', $params));
        }
        return escapeshellarg($params);
    }
    
    /**
     * ensures that the needed directories exist, where the DB files should be stored
     * @param string $target
     * @throws ZfExtended_Exception
     */
    protected function ensureSqlDirectoriesExist($target) {
        $neededDirectories = array(
                self::DIR_SQL_DATA => 'Database Data Files',
                self::DIR_SQL_STRUCTURE => 'Database Structure Files',
        );
        foreach($neededDirectories as $directory => $title) {
            $test = new SplFileInfo($target.DIRECTORY_SEPARATOR.$directory);
            if($test->isFile()) {
                throw new ZfExtended_Exception('ArchiveTaskBeforeDelete: Exporting Database, the '.$title.' Directory '.$directory.'does already exist as file!');
            }
            if(!$test->isDir()) {
                mkdir($test);
            }
            if(!$test->isDir() || $test->isDir() && !$test->isWritable()) {
                throw new ZfExtended_Exception('ArchiveTaskBeforeDelete: Exporting Database, the '.$title.' Directory '.$directory.' is not writeable!');
            }
        }
    }
    
    /**
     * creates the SQL command ready for exec
     * @param Zend_Config $credentials
     * @param bool $dataOnly → true to dump data only, false to dump structure only
     */
    protected function makeSqlCmd(Zend_Config $credentials, $dataOnly = true) {
        $mysqlExecutable = $this->getMysqlDumpExec();
        $cmd = array(escapeshellarg($mysqlExecutable));
        $cmd[] = '-h';
        $cmd[] = escapeshellarg($credentials->host);
        $cmd[] = '-u';
        $cmd[] = escapeshellarg($credentials->username);
        if(!empty($credentials->password)) {
            $cmd[] = '-p'.escapeshellarg($credentials->password);
        }
        $cmd[] = escapeshellarg($credentials->dbname);
        if($dataOnly) {
            $cmd[] = '--no-create-info --skip-triggers'; //data only
        }
        else {
            $cmd[] = '-d'; //structure only
        }
        $cmd[] = '%s %s > %s 2>&1';
        return join(' ', $cmd);
    }
    
    /**
     * returns the mysql import command for exec() 
     * @throws ZfExtended_Exception
     * @return string
     */
    protected function getMysqlDumpExec() {
        $config = Zend_Registry::get('config');
        if(!empty($config->runtimeOptions->plugins->ArchiveTaskBeforeDelete->mysqlDumpPath)){
            $exec = $config->runtimeOptions->plugins->ArchiveTaskBeforeDelete->mysqlDumpPath;
        }
        else {
            $exec = $config->resources->db->executable;
            if(!isset($exec)) {
                $exec = self::MYSQL_BIN;
            }
            $exec = dirname($exec).'/mysqldump';
        }
        if(!file_exists($exec) || !is_executable($exec)) {
            throw new ZfExtended_Exception("Cant find or execute mysqldump excecutable ".$exec);
        }
        return $exec;
    }
}