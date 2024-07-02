
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2024-06-19', 'TRANSLATE-4015', 'change', 'Export - Error on export of SDLXLIFF with inserted tag', 'Fix: SDLXLIFF track-changes export of inserted tag', '15'),
('2024-06-19', 'TRANSLATE-4003', 'change', 'Okapi integration - Okapi server config only on system level', 'The Okapi server config is changed to system level, so that it can only be changed via CLI interface and not anymore over the UI config.', '15'),
('2024-06-19', 'TRANSLATE-4012', 'bugfix', 'Editor general - RootCause: Cannot read properties of undefined (reading \'replace\')', 'translate-7.6.4: Implement logging to better trace this problem.', '15'),
('2024-06-19', 'TRANSLATE-4004', 'bugfix', 'Installation & Update - switching http https context on using sessionTokens', 'When accessing /editor instead /editor/ a redirect to http was made also in https context which might break translate5 integration scenarios ', '15'),
('2024-06-19', 'TRANSLATE-4001', 'bugfix', 'Workflows - ArchiveTaskActions may be stuck on old tasks and loose data', 'ArchiveTaskActions does not archive tasks if there are old tasks in state error and workflowstep filter is used.', '15'),
('2024-06-19', 'TRANSLATE-3999', 'bugfix', 'Auto-QA - Line length evaluation in Length check fires warnings', 'Fix: Line length evaluation in Length check fires warnings', '15'),
('2024-06-19', 'TRANSLATE-3968', 'bugfix', 'API, Authentication - Internal API not usable with application tokens', '7.6.4: Fix for sessions created via API token
7.6.2: Functionality which is using the Internal API is not usable with application tokens.', '15'),
('2024-06-19', 'TRANSLATE-3924', 'bugfix', 'TermPortal - TermTranslation terms do not appear in TermCollection', 'FIXED: problem with reimport translated term back to termcollection', '15');