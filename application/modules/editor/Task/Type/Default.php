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
 * The default task type is a mixture of project and task!
 */
class editor_Task_Type_Default extends editor_Task_Type_Abstract {
    const ID = 'default';

    protected bool $isInternalTask = false;
    protected bool $isProject = true;
    protected bool $isTask = true;
    protected bool $terminologyDisabled = false;
    protected bool $exportUsage = true;

    public function calculateImportTypes(bool $multiTarget, string &$projectType, string &$taskType) {
        //a default task may have only one target, otherwise its project/task combo
        if($multiTarget) {
            $projectType = editor_Task_Type_Project::ID;
            $taskType = editor_Task_Type_ProjectTask::ID;
        }
        else {
            $projectType = self::ID;
            $taskType = self::ID;
        }
    }
}
