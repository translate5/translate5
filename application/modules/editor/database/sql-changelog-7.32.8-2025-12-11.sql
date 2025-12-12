
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
--              http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
--
-- END LICENSE AND COPYRIGHT
-- */

-- userGroup calculation: basic: 1; editor: 2; pm: 4; admin: 8
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-12-11', 'TRANSLATE-5152', 'change', 'LanguageResources - Improve search of synchronisable language resource', '7.32.8: Improve search of synchronisable language resource', '15'),
('2025-12-11', 'TRANSLATE-5033', 'change', 'Import/Export - Improve visual PDF export to solve getting actual downloads in short intervals', 'IMPROVEMENT: Visual Export will be refreshed every time, it is initiated without any waiting-time', '15'),
('2025-12-11', 'TRANSLATE-5162', 'bugfix', 'InstantTranslate - 100% matches shown twice in InstantTranslate', 'Group matches in t5memory connector translate method call', '15'),
('2025-12-11', 'TRANSLATE-5157', 'bugfix', 'TermTagger integration - Problem with Termtagger creating invalid nesting in seldom circumstances', '* FIX: Termtagger may produces invalidly nested tags in rare situation - which are now automatically fixed', '15'),
('2025-12-11', 'TRANSLATE-5150', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - Logout on windows close never clean user session', 'Solves problem where user session was not cleaned up when closing the browser.', '15'),
('2025-12-11', 'TRANSLATE-5149', 'bugfix', 'Installation & Update - Implement a check in database:update to prevent FKs without explicit names', 'Newer DB version has changed foreign key naming scheme, therefore a check was implemented that on development always names are given for foreign keys.', '15'),
('2025-12-11', 'TRANSLATE-5148', 'bugfix', 'TermTagger integration - Bug in Termtagging Code changing term id\'s directly in segment targets', 'FIX: Changed terms might lead to markup-errors in a segments target if the segment contained the replaced term', '15'),
('2025-12-11', 'TRANSLATE-5143', 'bugfix', 'Editor general - RootCause: Cannot read properties of undefined (reading \'down\')', 'Fixed an error in the front-end on removing language resources from tasks in the clients tab', '15'),
('2025-12-11', 'TRANSLATE-5126', 'bugfix', 'InstantTranslate - Instant translate: open task for editing leaves task locked', 'Task will be unlocked and the job will be closed when user closes the browser.', '15'),
('2025-12-11', 'TRANSLATE-5098', 'bugfix', 'InstantTranslate - Customers file filters are not used for file extension checking', 'Custom customers file filters are not used for file extension checking when using file translation in InstantTranslate', '15');