
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2022-06-30', 'TRANSLATE-2984', 'feature', 'Task Management - Archive and delete old tasks', 'Implement a workflow action to export ended tasks, save the export (xliff2 and normal export) to a configurable destination and delete the task afterwards.
This action is disabled by default.', '15'),
('2022-06-30', 'TRANSLATE-2855', 'feature', 'MatchAnalysis & Pretranslation - Pre-translate pivot language with language resource', 'Pivot segments in task now can be be filled/translated using language resources. For api usage check this link: https://confluence.translate5.net/display/TAD/LanguageResources%3A+pivot+pre-translation', '15'),
('2022-06-30', 'TRANSLATE-2839', 'feature', 'OpenTM2 integration - Attach to t5memory service', 'Structural adjustments for t5memory service.', '15'),
('2022-06-30', 'TRANSLATE-2988', 'change', 'LanguageResources - Make translate5 fit for switch to t5memory', 'Add some fixes and data conversions when exporting a TMX from OpenTM2 so that it can be imported into t5memory.', '15'),
('2022-06-30', 'TRANSLATE-2992', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - PHP\'s setlocale has different default values', 'The PHP\'s system locale was not correctly set. This is due a strange behaviour setting the default locale randomly.', '15'),
('2022-06-30', 'TRANSLATE-2990', 'bugfix', 'OpenTM2 integration - Improve error handling on task re-import into TM', 'Sometimes the re-import a task into a TM feature was hanging and blocking the task. This is solved, the task is reopened in the case of an error and the logging was improved.', '15'),
('2022-06-30', 'TRANSLATE-2989', 'bugfix', 'Import/Export - XLIFF2 export is failing', 'The XLIFF 2 export was failing if the imported tasks was containing one file which was ignored on import (for example if all segments were tagged with translate no)', '15');