
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2023-12-14', 'TRANSLATE-3553', 'feature', 'TermPortal - Extend folder-based term import to work via sftp', 'Added support for terminology import from remote SFTP directory', '15'),
('2023-12-14', 'TRANSLATE-3550', 'feature', 'sso - Add client field for IdP and SSO', 'Added new config to define customer number field in SSO claims.', '15'),
('2023-12-14', 'TRANSLATE-3582', 'change', 'Editor general - Change behavior of reference field in editor', 'Reference field for tags validation is now considered to be "target at import time" not only if task is a review task, but also if "target at import time" contains some data.', '15'),
('2023-12-14', 'TRANSLATE-3580', 'change', 'LanguageResources - Remove NecTm plugin', 'NecTm plugin removed as deprecated', '15'),
('2023-12-14', 'TRANSLATE-3561', 'change', 't5memory - Enable t5memory connector to load balance big TMs', 'translate5 - 6.8.0: Fix overflow error when importing very big files into t5memory by splitting the TM internally.
translate5 - 6.8.2: Fix for data tooltip', '15'),
('2023-12-14', 'TRANSLATE-3612', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - Password reset does not work', 'Fix for password reset', '15'),
('2023-12-14', 'TRANSLATE-3609', 'bugfix', 'SpellCheck (LanguageTool integration), TermTagger integration - Detect horizonal scaling also behind single pool URL for a pooled service', 'Ebnable better horizonlal scaling for singular pool URLs for TermTagger & LanguageTool', '15'),
('2023-12-14', 'TRANSLATE-3607', 'bugfix', 't5memory - Non ASCII characters in document name leads to an error in t5memory', 'Fix for problem when segments are updated in t5memory and the response did contains non ASCII characters.', '15'),
('2023-12-14', 'TRANSLATE-3598', 'bugfix', 'Editor general - Fix PHP 8.1 warnings', 'Fix several PHP 8.1 warnings', '15'),
('2023-12-14', 'TRANSLATE-3597', 'bugfix', 'Editor general - Fix PHP 8.1 warnings', 'Fix several PHP 8.1 warnings', '15'),
('2023-12-14', 'TRANSLATE-3596', 'bugfix', 'Editor general - Fix PHP 8.1 warnings', 'Fix several PHP 8.1 warnings', '15'),
('2023-12-14', 'TRANSLATE-3595', 'bugfix', 'User Management - client PM can change password for admin users', 'FIX: client restricted PMs could edit user\'s with elevated roles', '15'),
('2023-12-14', 'TRANSLATE-3584', 'bugfix', 'Configuration - Implement a outbound proxy config', 'In hosted environments it might be necessary to route the outgoing traffic (visual downloads or similar) over a configurable proxy.', '15'),
('2023-12-14', 'TRANSLATE-3576', 'bugfix', 'LanguageResources - Microsoft and google language mapper problem', 'Fix for a problem with wrong language codes in google and microsoft resource.', '15'),
('2023-12-14', 'TRANSLATE-3414', 'bugfix', 'Import/Export - sdlxliff comments produce several problems', 'Fix problem where sdlxliff comment are not correctly processed on import and export.', '15'),
('2023-12-14', 'TRANSLATE-3284', 'bugfix', 'Task Management - Tasks in "competetive mode" get accepted automatically', 'FIXED: tasks are now not being auto-accepted when auto-opened after login', '15');