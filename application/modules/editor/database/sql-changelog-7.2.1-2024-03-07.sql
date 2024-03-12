
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2024-03-07', 'TRANSLATE-3752', 'feature', 'Editor general - Only display TM matches above a minimum match rate', 'Added new config for translation memory matches below the configured match rate will not be shown in the fuzzy match panel.', '15'),
('2024-03-07', 'TRANSLATE-3771', 'change', 'Editor general, usability editor - Highlight better the actual error in the right panel', 'UI improvements in the QA overview of an opened segment in the editor.', '15'),
('2024-03-07', 'TRANSLATE-3788', 'bugfix', 'User Management - change Mrs. to Ms. in user salutation', 'fix wrong English translation in the UI', '15'),
('2024-03-07', 'TRANSLATE-3783', 'bugfix', 't5memory - Fix sending save2disk parameter to t5memory', 't5memory did not properly store saved segments on disk due a wrong flag send by translate5.', '15'),
('2024-03-07', 'TRANSLATE-3779', 'bugfix', 'TermPortal - RootCause: [PromiseRejectionEvent] Ext.route.Router.onRouteRejection()', 'FIXED: javascript error popping when no default languages are configured for TermPortal', '15'),
('2024-03-07', 'TRANSLATE-3778', 'bugfix', 'TermPortal - RootCause: Cannot read properties of null (reading \'setAttribute\')', 'FIXED: UI problem with tooltips', '15'),
('2024-03-07', 'TRANSLATE-3777', 'bugfix', 'Task Management - RootCause: Cannot read properties of undefined (reading \'taskCustomField\')', 'FIXED: tooltip problem for custom field roles checkboxes group', '15'),
('2024-03-07', 'TRANSLATE-3763', 'bugfix', 'Editor general - RootCause: Cannot read properties of null (reading \'style\')', 'Fix a UI problem in the task/project add window.', '15'),
('2024-03-07', 'TRANSLATE-3744', 'bugfix', 'Editor general - Task events entity load', 'Fix for entity loading in the task events API endpoint.', '15'),
('2024-03-07', 'TRANSLATE-3663', 'bugfix', 'Editor general - Make custom fields editable', 'Defined custom field values for a task, can be edited.', '15'),
('2024-03-07', 'TRANSLATE-3420', 'bugfix', 'Import/Export - SDLxliff corrupt after export, if imported untranslated into translate5 and containing internal tags of type locked', 'Exported files with locked tags producing errors on re-import.', '15');