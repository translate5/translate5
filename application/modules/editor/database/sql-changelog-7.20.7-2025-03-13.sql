
-- /*
-- START LICENSE AND COPYRIGHT
--
--  This file is part of translate5
--
--  Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
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

-- userGroup calculation: basic: 1; editor: 2; pm: 4; admin: 8
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-03-13', 'TRANSLATE-4531', 'change', 'Export, Translate5 CLI - Implement a CLI task export command', 'A CLI task:export command was added.', '15'),
('2025-03-13', 'TRANSLATE-4534', 'bugfix', 'User Management - New admin users are not editable due wrong role combination', 'New admin users are created with a invalid role combination and are not editable therefore.', '15'),
('2025-03-13', 'TRANSLATE-4533', 'bugfix', 'job coordinator - coordinator goup dropdown and job coordinator role checkbox will dissapear after creation of job coordinator', 'User update: Fix roles render in User edit window', '15'),
('2025-03-13', 'TRANSLATE-4532', 'bugfix', 'job coordinator - coordinator group users with additional roles will not be editable for job coordinators', 'User edit: Fix Coordinator permissions to edit users with additional roles provided by admin', '15'),
('2025-03-13', 'TRANSLATE-4529', 'bugfix', 'LanguageResources - Language resource is not exported if it contains special characters', 'Fixed bug which may caused language resource assigned to a task to be not exported if its name contains some special characters', '15'),
('2025-03-13', 'TRANSLATE-4527', 'bugfix', 'Task Management - Tasks overview API end point returns duplicates for tasks', 'Task overview: fix duplicates in task list', '15'),
('2025-03-13', 'TRANSLATE-4520', 'bugfix', 'Import/Export - Type error in repetition update', 'Fix: Type error in repetition update fixed', '15'),
('2025-03-13', 'TRANSLATE-4471', 'bugfix', 'Import/Export - Worker-queue may stuck on import due to MatchAnalysis', 'translate5 - 7.20.7: FIX: Import stuck was still possible with lesser probability, fixed now
translate5 - 7.20.4: FIX: additional improvements
translate5 - 7.20.3: FIX: Import may stuck due to MatchAnalysis being queued too late', '15');