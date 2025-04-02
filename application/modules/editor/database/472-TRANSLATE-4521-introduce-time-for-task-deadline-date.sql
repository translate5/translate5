-- /*
-- START LICENSE AND COPYRIGHT
--
--  This file is part of translate5
--
--  Copyright (c) 2013 - '.(date('Y')).' Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
--
--  Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
--
--  This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
--  as published by the Free Software Foundation and appearing in the file agpl3-license.txt
--  included in the packaging of this file.  Please review the following information
--  to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
--  http://www.gnu.org/licenses/agpl.html
--
--  There is a plugin exception available for use with this release of translate5 for
--  translate5: Please see http://www.translate5.net/plugin-exception.txt or
--  plugin-exception.txt in the root folder of translate5.
--
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
-- 			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
--
-- END LICENSE AND COPYRIGHT
-- */

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`, `guiName`, `guiGroup`, `comment`)
VALUES ('runtimeOptions.workflow.autoCloseJobsTriggerTime', '1', 'editor', 'system',
        '21:00', '21:00', '', 'string',
        'Set the time (HH:MM, 24-hour format) when the system should trigger the job autoclose. Example: Set 21:30 and the auto-close will run from 21:30 every 15 min until end of day. All jobs that are overdue at 21:30 or will become overdue until end of day will be auto-closed. Important: works only if runtimeOptions.workflow.autoCloseJobs is enabled', 2
           , 'Workflow: time of the day to auto-close jobs', 'Workflow: auto-close jobs', '');

UPDATE `Zf_configuration`
SET description = 'When set, all jobs for a project with a job-deadline set will be auto-closed at the configured auto-close trigger time when overdue.'
WHERE `name` = 'runtimeOptions.workflow.autoCloseJobs';