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
 * Fix TRANSLATE-2082 by readding the missing tags to the target
 */
set_time_limit(0);

//uncomment the following line, so that the file is not marked as processed:
//$this->doNotSavePhpForDebugging = false;

//should be not __FILE__ in the case of wanted restarts / renamings etc
// and must not be a constant since in installation the same named constant would we defined multiple times then
$SCRIPT_IDENTIFIER = '244-editor-mysql-TRANSLATE-2082-fix-target-tags.php';

/* @var $this ZfExtended_Models_Installer_DbUpdater */

$argc = count($argv);
if(empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

/**
 * Override XLF fileparser to
 * 1. get segmentId from stored placeholder in skeletion
 * 2. then remove the placeholder, so that a new placeholder with the surrounding tags is created
 */
class FixXlfTranslate2082 extends editor_Models_Import_FileParser_Xlf {
    /**
     * @var integer
     */
    protected $foundLekSegmentId;
    
    public function __construct($fileId, $task) {
        $path = $task->getAbsoluteTaskDataPath().sprintf(editor_Models_File::SKELETON_PATH, $fileId);
        $backup = $path.'.backup';
        if(!file_exists($backup)) {
            copy($path, $backup);
        }
        parent::__construct($path, basename($path), $fileId, $task);
        $this->_origFile = gzuncompress($this->_origFile);
        $this->contentConverter->xmlparser->registerElement('lektargetseg', function($tag, $attributes, $idx){
            $this->foundLekSegmentId = $attributes['id'];
        }, function($tag, $index){
            $this->contentConverter->xmlparser->replaceChunk($index, '');
        });
    }
    
    protected function setAndSaveSegmentValues(){
        return $this->foundLekSegmentId;
    }
    
    public function parseFile() {
        $this->parse();
        file_put_contents($this->_path, gzcompress($this->getSkeletonFile()));
        error_log("Converted and saved ".$this->_path);
    }
}

/**
 * Override to make the internal $xmlparser accessable for adding custom handlers above
 */
class FixXlfTranslate2082_ContentConverter extends editor_Models_Import_FileParser_Xlf_ContentConverter {
    /**
     * @var editor_Models_Import_FileParser_XmlParser
     */
    public $xmlparser = null;
}

//register ContentConverter override
ZfExtended_Factory::addOverwrite('editor_Models_Import_FileParser_Xlf_ContentConverter', 'FixXlfTranslate2082_ContentConverter');

$sfm = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');

$sql = 'SELECT file.`taskGuid`, file.`id`
FROM `LEK_task` task, `LEK_files` file
WHERE task.`importAppVersion` = "3.4.3"
    AND task.`taskGuid` = file.`taskGuid`
    AND file.`fileParser` = "editor_Models_Import_FileParser_Xlf"
    AND task.`taskGuid` NOT IN (
        SELECT `taskGuid` FROM `LEK_task_migration` WHERE `filename` = ?
    ) ';

$db = Zend_Db_Table::getDefaultAdapter();
$res = $db->query($sql, $SCRIPT_IDENTIFIER);
$tasksAndFiles = $res->fetchAll();
$files = [];
foreach($tasksAndFiles as $data) {
    settype($files[$data['taskGuid']], 'array');
    $files[$data['taskGuid']][] = $data['id'];
}

$taskCount = count($files);
$tasksDone = 1;
error_log($SCRIPT_IDENTIFIER.': Tasks to be converted: '.$taskCount);

foreach($files as $taskGuid => $taskFiles) {
    $task = ZfExtended_Factory::get('editor_Models_Task');
    /* @var $task editor_Models_Task */
    $task->loadByTaskGuid($taskGuid);
    foreach($taskFiles as $fileId) {
        $xlf = new FixXlfTranslate2082($fileId, $task);
        $sfm->initFields($task->getTaskGuid());
        $xlf->setSegmentFieldManager($sfm);
        $xlf->parseFile();
    }
    error_log('Task '.($tasksDone++).' of '.$taskCount." done.");
    $res = $db->query('INSERT INTO LEK_task_migration (`taskGuid`, `filename`) VALUES (?,?)', [$taskGuid, $SCRIPT_IDENTIFIER]);
}

$res = $db->query($sql, $SCRIPT_IDENTIFIER);
$tasksAndFiles = $res->fetchAll();
if(!empty($tasksAndFiles)) {
    $this->doNotSavePhpForDebugging = false; //enable restart script when conversion was not complete
    $msg = $SCRIPT_IDENTIFIER.': Problem could not fix all skeletonfiles! Please restart script!';
    error_log($msg);
    echo $msg;
}
