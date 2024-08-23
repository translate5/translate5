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

DELETE FROM `Zf_configuration` WHERE `name` = 'runtimeOptions.import.projectDeadline.jobAutocloseSubtractPercent';

INSERT INTO Zf_configuration (`name`,`confirmed`,`module`,`category`,`value`,`default`,`defaults`,`type`,`typeClass`,`description`,`level`,`accessRestriction`,`guiName`,`guiGroup`,`comment`)
VALUES ('runtimeOptions.import.projectDeadline.jobDeadlineFraction', 1, 'editor', 'import', 100, 100, null, 'integer', null, 'This setting determines the percentage by which the users jobs will be closed before the project-deadline (if one is set). 100% means, the job deadline equals the project-deadline. Do not set to 0, which would immediately finish the job after creation.', 4, 'none', 'Project Deadline: Auto-Close Deadline Configuration', 'Workflow', null);

INSERT INTO Zf_configuration (`name`,`confirmed`,`module`,`category`,`value`,`default`,`defaults`,`type`,`typeClass`,`description`,`level`,`accessRestriction`,`guiName`,`guiGroup`,`comment`)
VALUES ('runtimeOptions.import.projectDeadline.autoCloseJobs', 1, 'editor', 'import', 0, 0, null, 'boolean', null, 'When set, all jobs for a project with the deadline set will be auto-closed. This takes the configured "jobDeadlineFraction" into account.', 16, 'none', 'Project Deadline: Auto-Close Jobs', 'Workflow', null);
