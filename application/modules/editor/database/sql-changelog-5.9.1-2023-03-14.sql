
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2023-03-14', 'TRANSLATE-3117', 'feature', 'Import/Export - translator package', '5.9.0: Editor users are now able to download a zip package including everything needed to translate a job outside of translate5 and afterwards update the task with it.
5.9.1: Fix - enable reimport package for non pm users', '15'),
('2023-03-14', 'TRANSLATE-3245', 'change', 'VisualReview / VisualTranslation - Replace webserver in pdfconverter to nginx', 'Fixed problem which caused pdfconverter container fail to start', '15'),

', '15'),
('2023-03-14', 'TRANSLATE-3242', 'bugfix', 'MatchAnalysis & Pretranslation - Fix match analysis on API usage', '- Task is now locked immediately after match analysis is scheduled.
- PauseMatchAnalysis worker now returns an error in case after maximum wait time language resource is still not available.
- Documentation updated', '15'),
('2023-03-14', 'TRANSLATE-3240', 'bugfix', 'TBX-Import, TermPortal - Re-create term portal disk images on re-import', 'Images missing on disk are now recreated during tbx import', '15'),
('2023-03-14', 'TRANSLATE-3239', 'bugfix', 'Authentication - Unify HTTPS checks for usage behind proxy with ssl offloaded', 'Fix that SSO and CLI auth:impersonate is working behind a proxy with SSL offloading.', '15'),
('2023-03-14', 'TRANSLATE-3237', 'bugfix', 'Configuration, Editor general - UI: User config requested before loaded', 'Fixed bug popping sometimes if config store is not yet loaded', '15'),
('2023-03-14', 'TRANSLATE-3235', 'bugfix', 'Okapi integration, TermPortal - Internal term translations should always use the system default bconf', 'System default bconf is now used for termtranslation-tasks', '15');