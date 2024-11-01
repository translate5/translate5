
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2024-11-01', 'TRANSLATE-4246', 'change', 'LanguageResources - Adding Terminology to OpenAI translation Requests when importing tasks or do a pretranslation in the Analysis', 'Improvement: OpenAI Plugin uses assigned Terminology when translating', '15'),
('2024-11-01', 'TRANSLATE-4243', 'change', 't5memory - Wait until reorganization is finished in background tasks', 'Background running tasks now wait until t5memory reorganization is finished.', '15'),
('2024-11-01', 'TRANSLATE-4240', 'change', 'file format settings - Remove warning when using a custom BCONF in the zip', 'Remove warning when using a custom BCONF in the import-ZIP', '15'),
('2024-11-01', 'TRANSLATE-4213', 'change', 'Configuration - Define Callback in config client-overwritable', 'triggerCallbackAction URLs are configurable through the config now, with the ability to override it at the client level. Users can define or modify the callback URLs without need for manual database changes.', '15'),
('2024-11-01', 'TRANSLATE-4254', 'bugfix', 'LanguageResources - Client-PM can only filebased LR-types', 'FIX: a client-PM could add only filebased LR-types, added non-filebased', '15'),
('2024-11-01', 'TRANSLATE-4250', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - Small tasks hang on import due to delayed workers with no cronjobs enabled Edit Add comment Assign More Waiting for support  Share this issue Export', 'FIX: A bug in the worker scheduler lead to hanging import on small tasks in conjuction with worker-delay', '15'),
('2024-11-01', 'TRANSLATE-4238', 'bugfix', 'Editor general - Only my project button produces UI error when clicked from menu', 'Fix for UI problem when filtering out "Only my projects" from menu item in project grid.', '15');