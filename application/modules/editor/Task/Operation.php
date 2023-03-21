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
 * A simple Overview class that holds the identifiers for all operations and creates the start/finishing worker to wrap an operation
 */
class editor_Task_Operation {
    
    // Every operation (also from Plugins) should define their type here to have an overview about the possible operations
    // these keys presumably also be used to find code related to the operation, so please keep them pretty unique !
    /**
     * @var string
     */
    const MATCHANALYSIS = 'matchanalysis';

    /**
     * @var string
     */
    const AUTOQA = 'autoqa';

    /**
     * @var string
     */
    const TAGTERMS = 'tagterms';

    /**
     * @var string
     */
    const PIVOT_PRE_TRANSLATION = 'pivotpretranslation';

    /**
     * 
     * @param string $operationType: must be a constant of this class
     * @param editor_Models_Task $task
     * @return int The parent ID to use for all inner workers
     */
    public static function create(string $operationType, editor_Models_Task $task) : int {
        $taskState = $task->getState();
        // Only one operation is allowed to run at a time !
        if(in_array($taskState, self::getAllOperations())){
            throw new editor_Task_Operation_Exception('E1396', ['taskstate' => $taskState]);
        }
        // we do not want excelExports to be manipulated
        if($taskState === editor_Models_Task::STATE_EXCELEXPORTED){
            throw new editor_Task_Operation_Exception('E1395', ['taskstate' => $taskState, 'operation' => $operationType]);
        }
        $worker = ZfExtended_Factory::get(editor_Task_Operation_StartingWorker::class);
        if($worker->init($task->getTaskGuid(), [ 'operationType' => $operationType ])) {
            $parentId = $worker->queue(0, null, false);
            // add finishing worker
            $worker = ZfExtended_Factory::get(editor_Task_Operation_FinishingWorker::class);
            if($worker->init($task->getTaskGuid(), [ 'operationType' => $operationType, 'taskInitialState' => $taskState ])) {
                $worker->queue($parentId, null, false);
                return $parentId;
            }
        }
        return 0;
    }

    /**
     * retrieves all Operations
     * @return string[]
     */
    public static function getAllOperations() : array {
        return [ self::AUTOQA, self::MATCHANALYSIS, self::PIVOT_PRE_TRANSLATION ];
    }
}
