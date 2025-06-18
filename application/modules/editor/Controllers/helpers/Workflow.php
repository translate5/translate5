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

use MittagQI\Translate5\Workflow\Assert\WriteableWorkflowAssert;

/**
 * provides reusable workflow methods for controllers
 */
class Editor_Controller_Helper_Workflow extends Zend_Controller_Action_Helper_Abstract
{
    /**
     * checks the user state of given taskGuid and userGuid,
     * throws a ZfExtended_NoAccessException if user is not allowed to write to the loaded task
     * @throws ZfExtended_NoAccessException
     */
    public function checkWorkflowWriteable(string $taskGuid, string $userGuid, editor_Workflow_Default $workflow = null)
    {
        WriteableWorkflowAssert::create()->assert($taskGuid, $userGuid, $workflow);
    }
}
