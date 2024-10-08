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

INSERT INTO LEK_workflow_action (workflow, `trigger`, inStep, byRole, userState, actionClass, action, parameters, position, description) 
VALUES ('default', 'doCronPeriodical', null, null, null, '\\MittagQI\\Translate5\\Workflow\\Actions\\AutocloseJob', 'closeByDeadline', null, DEFAULT, null);


ALTER TABLE LEK_task
add column deadlineDate datetime null;

INSERT INTO Zf_configuration (name, confirmed, module, category, value, `default`, defaults, type, typeClass, description, level, accessRestriction, guiName, guiGroup, comment)
VALUES ('runtimeOptions.import.projectDeadline.jobAutocloseSubtractPercent', 1, 'editor', 'import', null, null, null, 'integer', null, 'This setting determines the percentage by which the user jobs'' deadlines will be reduced from the overall project deadline date. The task jobs will be automatically marked as auto-finish earlier, based on the specified percentage of the total project deadline.', 2, 'none', 'Project Import: Auto-Close Timing Configuration', 'Import', null);