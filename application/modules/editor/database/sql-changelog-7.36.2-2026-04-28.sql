
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2026-04-28', 'TRANSLATE-5447', 'change', 'Content Protection - ContentProtection improve default rule', 'Improve default rule', '15'),
('2026-04-28', 'TRANSLATE-5381', 'change', 'Editor general, Security Related - Add headers suggested by HTTP Observatory', '7.36.2: help video fix
7.36.0: Some new headers were added for security reasons.
Content-Security-Policy and X-Frame-Options may influence how the application works if you have custom scripts or styles loaded from different source or you load translate5 in <iframe> or <embed>
To make headers be configurable we added the following config values, which can be added to installation.ini: 
runtimeOptions.headers.enableXFrameHeader
runtimeOptions.headers.defaultSrcUrls
runtimeOptions.headers.scriptSrcUrls
runtimeOptions.headers.connectSrcUrls
runtimeOptions.headers.styleSrcUrls
runtimeOptions.headers.imgSrcUrls
runtimeOptions.headers.fontSrcUrls

Please check the reference in the application.ini file', '15'),
('2026-04-28', 'TRANSLATE-5372', 'change', 'Task Management - Language selection in Create Project wizard through comma-separated input', 'Added ability to paste comma-separated language codes (.g. “cs-CZ, de-DE, es-ES, ja-JP") into \'Target language\'-tagfield in project import wizard', '15'),
('2026-04-28', 'TRANSLATE-5462', 'bugfix', 'LanguageResources - Deep linking for Language Resources overview', 'Improved navigation by adding deep-link support for Language Resources, including direct jump-and-select from task-assigned language resources and URL-based state restoration.', '15'),
('2026-04-28', 'TRANSLATE-5461', 'bugfix', 'Okapi integration - Add --list option to okapi:upgrade-to-latest to show available Okapi endpoints per configured server', 'Added a new --list mode to okapi:upgrade-to-latest so admins can preview available Okapi endpoints per configured server without performing an upgrade.', '15'),
('2026-04-28', 'TRANSLATE-5433', 'bugfix', 'translate5 AI - Translate5AI: TQE Stop retrying when language resource uses incompatible model', 'Fix problem where TQE will re-try to evaluate segment with resource with invalide model configured.', '15'),
('2026-04-28', 'TRANSLATE-5430', 'bugfix', 't5memory - Escaped apostrophe processed incorrectly in TMX import', 'Fix html entities processing in TMX import', '15'),
('2026-04-28', 'TRANSLATE-5428', 'bugfix', 'Workflows - inconsistent workflow prevents working in task', 'Fix user permissions to enter tasks', '15'),
('2026-04-28', 'TRANSLATE-5248', 'bugfix', 'Export - Check user permissions in task export action', '7.36.2: Fix InstantTranslate downloads
7.36.0: Use permission checks in task export action', '15'),
('2026-04-28', 'TRANSLATE-5010', 'bugfix', 'Task Management, User Management - Performance issues with userlistAction', 'Loading users for tasks may lead to excessive full scans and per-row subquery execution, slowing down the DB. This is optimised.', '15');