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

/**
 * Abstract File Writer for Plugin SegmentStatistics
 */
abstract class editor_Plugins_SegmentStatistics_Models_Export_Abstract {
    const TYPE_IMPORT = 'import';
    const TYPE_EXPORT = 'export';
    
    /**
     * @var editor_Models_Task
     */
    protected $task;
    
    /**
     * @var string
     */
    protected $taskGuid;
    
    /**
     * @var string
     */
    protected $type;
    
    /**
     * @var string
     */
    protected $statistics;
    
    /**
     * @var boolean
     */
    protected $debug = false;
    
    public function init(editor_Models_Task $task, stdClass $statistics, array $workerParams) {
        $this->task = $task;
        $this->taskGuid = $task->getTaskGuid();
        $this->type = $workerParams['type'];
        //prevent internal restructuring to destruct other algorithms:
        $this->statistics = clone $statistics; 
        $this->debug = ZfExtended_Debug::hasLevel('plugin', 'SegmentStatistics');
    }
    
    /**
     * Writes the Statistics in the given Format to the disk
     * Filename without suffix, suffix is appended by this method
     * @param string $filename
     */
    abstract public function writeToDisk(string $filename);
}