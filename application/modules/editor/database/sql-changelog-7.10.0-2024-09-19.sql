
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2024-09-19', 'TRANSLATE-4173', 'change', 'InstantTranslate - InstantTranslate: Move automatically triggered "manual translate" button on resource level', 'manual translation button is now shown for each resource, if it\'s slow', '15'),
('2024-09-19', 'TRANSLATE-4149', 'change', 'SpellCheck (LanguageTool integration), TermTagger integration - Segment processing may processes segments simultaneously', 'FIX: Auto-QA processing may had bugs processing segments simultaneously and overwriting results', '15'),
('2024-09-19', 'TRANSLATE-4039', 'change', 'InstantTranslate - Request assigned languageResources in InstantTranslate in the Frontend', 'IMPROVEMENT: InstantTranslate requests the attached Resources individually from the Frontend to bring request-times down', '15'),
('2024-09-19', 'TRANSLATE-4192', 'bugfix', 'Workflows - job status "autoclose" preselected when assigning users', 'Fix default pre-selected job state.', '15'),
('2024-09-19', 'TRANSLATE-4180', 'bugfix', 'Installation & Update - Prevent default plugin activation for updates', 'Since 7.8.0 default plugins were activated by default. This was also done on updates, so by purposes deactivated default plugins were reactivated automatically. This is fixed, so that default plug-ins are only activated on installations.', '15'),
('2024-09-19', 'TRANSLATE-4179', 'bugfix', 'Editor general - Fix html escaping in concordance search', 'Remove unneeded html escaping in concordance search grid result', '15'),
('2024-09-19', 'TRANSLATE-4178', 'bugfix', 'Editor general - comments to a segment starting with < will be empty', 'Fix saving segment comments containing special characters', '15'),
('2024-09-19', 'TRANSLATE-4174', 'bugfix', 'Editor general - Language resource name wrongly escaped in Match rate grid', 'Fix Language resource name escaping in Match rate grid', '15'),
('2024-09-19', 'TRANSLATE-4056', 'bugfix', 'TermTagger integration - Delayed Workers: Improve Termtagging & Spellchecking to not stop when Containers are busy', 'Enhancement: When a single Segment have a TermTagger error in the Import, a warning is reported to the task-events instead of an exception rendering the task erroneous', '15');