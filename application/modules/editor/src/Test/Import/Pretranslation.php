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
 * Represents the api-request configuration for a pretranslation operation
 */
final class Pretranslation extends Resource
{
    public int $internalFuzzy = 1;
    public int $pretranslateMatchrate = 100;
    public int $pretranslateTmAndTerm = 1;
    public int $pretranslateMt = 0;
    public int $isTaskImport = 0;
    private int $_taskId;

    /**
     * @param int $taskId
     * @return $this
     */
    public function setTaskId(int $taskId){
        $this->_taskId = $taskId;
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
        if(empty($this->_taskId)){
            throw new Exception('Pretranslation has no taskId assigned');
        }
        $api->putJson(
            'editor/task/' . $this->_taskId . '/pretranslation/operation',
            $this->getRequestParams(),
            null,
            false
        );
        $this->_requested = true;
    }

    public function cleanup(Helper $api, Config $config): void
    {
        // only to fullfill abstract implementation, not needed here
    }
}
