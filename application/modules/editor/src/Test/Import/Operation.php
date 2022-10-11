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

namespace MittagQI\Translate5\Test\Import;

use MittagQI\Translate5\Test\Api\Helper;

/**
 * Abstract base for task-operations
 */
abstract class Operation extends Resource
{
    protected int $_taskId;
    protected string $_taskGuid;

    /**
     * @param int $taskId
     * @return $this
     */
    public function setTask(Task $task){
        $this->_taskId = $task->getId();
        $this->_taskGuid = $task->getTaskGuid();
        return $this;
    }
    /**
     * Queues the analysis
     * @param Helper $api
     * @param int $taskId
     * @throws \Zend_Http_Client_Exception
     */
    public function import(Helper $api, Config $config): void
    {
        if($this->_requested){
            throw new Exception('You cannot import a '.get_class($this).' twice.');
        }
        if(empty($this->_taskId)){
            throw new Exception('Pretranslation has no taskId assigned');
        }
        $this->request($api);
        $this->_requested = true;
    }

    /**
     * Implements the actual request
     * @param Helper $api
     * @param Config $config
     */
    abstract protected function request(Helper $api): void;

    public function cleanup(Helper $api, Config $config): void
    {
        // only to fullfill abstract implementation, not needed here
    }
}
