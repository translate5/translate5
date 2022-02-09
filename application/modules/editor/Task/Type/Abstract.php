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
 * Encapsulates all task types and task type tests
 */
abstract class editor_Task_Type_Abstract {
    /**
     * A internal task is not listed as task by the task controller, since they are not accessible for users. They are needed only for internal processing.
     * @var bool
     */
    protected bool $isInternalTask;

    /**
     * A internal task is not listed as task by the task controller, since they are not accessible for users. They are needed only for internal processing.
     * @var bool
     */
    protected bool $isProject;

    /**
     * FIXME describe what this flag is used for!
     * @var bool
     */
    protected bool $isTask;

    /**
     * Usage of term tagging is disabled for that type
     * @var bool
     */
    protected bool $terminologyDisabled;

    /**
     * Usage of term tagging is disabled for that type
     * @var bool
     */
    protected bool $exportUsage;

    /**
     * Returns true if usage of term tagging is disabled for that type
     * @return bool
     */
    public function isTerminologyDisabled(): bool
    {
        return $this->terminologyDisabled;
    }

    /**
     * returns true if task is internal, so not listed as task in the task controller since not accessible there fore users. Only for internal processing.
     * @return bool
     */
    public function isInternalTask(): bool {
        return $this->isInternalTask;
    }

    /**
     * Returns true if the task is of type project
     * @return bool
     */
    public function isProject(): bool {
        return $this->isProject;
    }

    /**
     * Returns true if the task is of type task
     * @return bool
     */
    public function isTask(): bool
    {
        return $this->isTask;
    }

    /**
     * @return bool
     */
    public function isExportUsage(): bool
    {
        return $this->exportUsage;
    }

    public function id(): string {
        return $this::ID;
    }

    public function __toString(): string
    {
        return $this->id();
    }

}
