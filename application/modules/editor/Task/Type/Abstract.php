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
abstract class editor_Task_Type_Abstract
{
    /**
     * An internal task is not listed as task by the task controller, since they are not accessible for users.
     * They are needed only for internal processing.
     */
    protected bool $isInternalTask;

    /**
     * A internal task is not listed as task by the task controller, since they are not accessible for users.
     * They are needed only for internal processing.
     */
    protected bool $isProject;

    /**
     * For historical reasons we have tasks beeing task and project at the same time.
     * Therefore we need that isTask property to distinguish between such hybrid taskprojects
     *  and real projects where isTask would be false then
     */
    protected bool $isTask;

    /**
     * Usage of term tagging is disabled for that type
     */
    protected bool $terminologyDisabled;

    /**
     * Usage of AutoQA is enabled for that type
     */
    protected bool $autoStartAutoQA;

    /**
     * Usage of term tagging is disabled for that type
     */
    protected bool $exportUsage;

    /**
     * Forces the system default file-format-settings to be used on import
     */
    protected bool $useSysDefaultFileFormatSettings = false;

    protected bool $supportsTaskTm = true;

    /**
     * Returns true if usage of term tagging is disabled for that type
     */
    public function isTerminologyDisabled(): bool
    {
        return $this->terminologyDisabled;
    }

    /**
     * Returns true if usage of AutoQA is enabled for that type
     */
    public function isAutoStartAutoQA(): bool
    {
        return $this->autoStartAutoQA;
    }

    /**
     * true if task is internal, so not listed as task in the task controller since not accessible for users.
     * Only for internal processing.
     */
    public function isInternalTask(): bool
    {
        return $this->isInternalTask;
    }

    /**
     * Returns true if the task is of type project
     */
    public function isProject(): bool
    {
        return $this->isProject;
    }

    /**
     * Returns true if the task is of type task
     */
    public function isTask(): bool
    {
        return $this->isTask;
    }

    public function isExportUsage(): bool
    {
        return $this->exportUsage;
    }

    /**
     * Returns if the task must be imported using the system default file-format settings
     */
    public function useSystemDefaultFileFormatSettings(): bool
    {
        return $this->useSysDefaultFileFormatSettings;
    }

    public function supportsTaskTm(): bool
    {
        return $this->supportsTaskTm;
    }

    public function id(): string
    {
        return $this::ID;
    }

    /**
     * calculates the project and task types to be used out of the current type
     * (which was the desired one) and the multiTarget info
     */
    abstract public function calculateImportTypes(bool $multiTarget, string &$projectType, string &$taskType);

    public function __toString(): string
    {
        return $this->id();
    }
}
