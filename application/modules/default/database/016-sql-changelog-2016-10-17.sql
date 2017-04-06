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
--  translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
--  Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
--  folder of translate5.
--   
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
-- 			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
-- 
-- END LICENSE AND COPYRIGHT
-- */

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `title`, `description`, `userGroup`) VALUES ('2016-10-17', 'TRANSLATE-726', 'New Column "type" in ChangeLog Plugin', 'A new column „type“ was added in the change-log grid, to show directly the type (bugfix, new feature, change) of a change log entry.', '14'),
('2016-10-17', 'TRANSLATE-743', 'Implement filters in change-log grid', 'The change-log grid can be filtered right now.', '14'),
('2016-10-17', 'TRANSLATE-612', 'User-Authentication via API - enable session deletion, login counter', 'Already existing sessions can now be deleted via API, the counter of invalid logins increases on invalid logins via API (restrictions are currently not implemented).', '8'),
('2016-10-17', 'TRANSLATE-644', 'enable editor-only usage in translate5 - enable direct task association', 'When creating the user session for the embedded editor a task to be opened can be associated to the session.', '8'),
('2016-10-17', 'TRANSLATE-750', 'Make API auth default locale configurable', 'When using the User Authentication API the default locale can be configured now.', '8'),
('2016-10-17', 'TRANSLATE-684', 'Introduce match-type column - fixing tests', 'Fixing the API tests according to the new column.', '8'),
('2016-10-17', 'TRANSLATE-745', 'double tooltip on columns with icon in taskoverview', 'Since ExtJS6 Update the native tooltip implementation was in conflict with an custom implementation. This is solved.', '14'),
('2016-10-17', 'TRANSLATE-749', 'session->locale sollte an dieser Stelle bereits durch LoginController gesetzt sein', 'Fixed seldom issue on login where the the following error was produced:“session ? locale sollte an dieser Stelle bereits durch LoginController gesetzt sein“', '8'),
('2016-10-17', 'TRANSLATE-753', 'change-log-window is not translated on initial show', 'When he change-log window was opened automatically, it was not translated properly.', '14');
