
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2026-01-08', 'TRANSLATE-5176', 'change', 'LanguageResources - Force service name for language resources', 'Fill internal DB field with proper service type of the affected language resource', '15'),
('2026-01-08', 'TRANSLATE-5185', 'bugfix', 'InstantTranslate - Enable multi language picker in instant translate enabled by default', 'Multi select for file translation will be enabled by default in instant translate.', '15'),
('2026-01-08', 'TRANSLATE-5181', 'bugfix', 'Search & Replace (editor) - RootCause: Cannot read properties of null (reading \'editor\')', 'FIXED: problem with Search/Replace dialog', '15'),
('2026-01-08', 'TRANSLATE-5126', 'bugfix', 'InstantTranslate - Instant translate: open task for editing leaves task locked', '7.32.10: Deactivated the buggy unlock on browser close functionality until fixes are ready
7.32.8: Task will be unlocked and the job will be closed when user closes the browser.', '15'),
('2026-01-08', 'TRANSLATE-4338', 'bugfix', 'TrackChanges - Deleted and added MQM-tags look the same with trackChanges', 'FIXED: added missing striked-through style for MQM-tags in segments grid', '15');