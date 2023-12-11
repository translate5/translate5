
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2023-12-07', 'TRANSLATE-3608', 'change', 'Configuration - Improve edit 100% matches config desciption', 'Improvement in 100% matches config (runtimeOptions.frontend.importTask.edit100PercentMatch) description.', '15'),
('2023-12-07', 'TRANSLATE-3610', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - FIX bug in Sanitization with empty params', 'FIX: Possible unneccessary exception when sanitizing params', '15'),
('2023-12-07', 'TRANSLATE-3606', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.), User Management - Session API authentication combined with apptokens leads to beeing the wrong user', 'FIX authentication via POST on the session-controller, where elevated credentials were delivered when called with an App-Token', '15'),
('2023-12-07', 'TRANSLATE-3605', 'bugfix', 'LanguageResources - TM button for associated tasks missing', 'Fix problem where the TM button for associated tasks was not visible in resources overview.', '15');