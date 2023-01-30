
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2022-12-20', 'TRANSLATE-764', 'change', 'Import/Export - Restructuring of export.zip', 'UPDATE: The content structure of the export zip changed. In the future it does NOT contain any more a folder with the task guid, but directly on the highest level of the zip all files of the task that were translated/reviewed.', '15'),
('2022-12-20', 'TRANSLATE-3150', 'bugfix', 'TermPortal - TermPortal: term status tooltips old locale after locale changed', 'Search results icons tooltips language is now changed on GUI language change', '15'),
('2022-12-20', 'TRANSLATE-3149', 'bugfix', 'Task Management, WebSocket Server - 403 Forbidden messages in opened task', 'Users with an unstable internet connection got multiple 403 Forbidden error messages.', '15'),
('2022-12-20', 'TRANSLATE-3145', 'bugfix', 'TermPortal - TermPortal: problem on creating Chinese term', 'fixed problem popping on creating term in Chinese language', '15'),
('2022-12-20', 'TRANSLATE-3144', 'bugfix', 'Export - Task export crashes with apache internal server error - no PHP error', 'If the tasks name contains non printable invalid UTF8 characters, the task was not exportable.', '15'),
('2022-12-20', 'TRANSLATE-3130', 'bugfix', 'User Management - Login name with space or maybe other unusual characters causes problems', 'User validator was changed to prevent creating users with login name containing a space character', '15'),
('2022-12-20', 'TRANSLATE-3123', 'bugfix', 'Import/Export - Tbx import: handling duplicated attributes', 'TBX import: removed term-level attributes duplicates', '15');