
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2022-02-24', 'TRANSLATE-2852', 'change', 'TermPortal - Allow role TermPM to start Term-Translation-Workflow', 'termPM-role is now sifficient for Transfer-button to be shown.
TermPortal filter window will assume *-query if yet empty.', '15'),
('2022-02-24', 'TRANSLATE-2851', 'change', 'TermPortal - Security dialogue, when deleting something in TermPortal', 'Added confirmation dialogs on term/attribute deletion attempt', '15'),
('2022-02-24', 'TRANSLATE-2856', 'bugfix', 'API, Editor general - Login/Logout issues', 'Fixed a race condition on logout that sometimes resulted in HTML being parsed as javascript.', '15'),
('2022-02-24', 'TRANSLATE-2853', 'bugfix', 'Editor general - User association error', 'Solves problem when assigning users in import wizard after a workflow is changed and the current import produces only one task.', '15'),
('2022-02-24', 'TRANSLATE-2846', 'bugfix', 'Task Management - Filter on QA errors column is not working', 'FIX: Sorting/Filtering of column "QS Errors" in task grid now functional', '15'),
('2022-02-24', 'TRANSLATE-2818', 'bugfix', 'Auto-QA - Length-Check must Re-Evaluate also when processing Repititions', 'FIX: AutoQA now re-evaluates the length check for each segment individually when saving repititions', '15');