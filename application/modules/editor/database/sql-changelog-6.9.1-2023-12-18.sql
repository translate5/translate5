
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2023-12-18', 'TRANSLATE-3553', 'feature', 'TermPortal - Extend folder-based term import to work via sftp', 'translate5 - 6.9.0: Added support for terminology import from remote SFTP directory
translate5 - 6.9.1: Added additional config value check', '15'),
('2023-12-18', 'TRANSLATE-3626', 'bugfix', 't5memory - Write to instant translate t5memory memory', 'Fix for writing to instant-translate memory.', '15'),
('2023-12-18', 'TRANSLATE-3619', 'bugfix', 'Editor general - SQL error when filtering repetitions with bookmarks', 'FIXED: sql-error when both bookbarks and repetiions filters are used', '15'),
('2023-12-18', 'TRANSLATE-3419', 'bugfix', 'Task Management - Click on PM name in project overview opens mail with undefined address - and logs out user in certain cases', 'translate5 - 6.7.0: FIXED: \'mailto:undefined\' links in PM names in Project overview
translate5 - 6.9.1: project task grid fix', '15');