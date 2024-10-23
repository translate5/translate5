
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2024-10-04', 'TRANSLATE-4206', 'change', 'TM Maintenance - Add user name and timestamp to error modal in TMMaintenance', 'Added debug info to the error modal in TM Maintenance', '15'),
('2024-10-04', 'TRANSLATE-4201', 'change', 'InstantTranslate - InstantTranslate: If multi-segment: Highlight different resources in result', 'Multi-segment mode: best results from different language resources are highlighted and merged into single result block', '15'),
('2024-10-04', 'TRANSLATE-4198', 'change', 't5memory - Instruction how to enable TM Maintenance', 'TM Maintenance is now enabled by default', '15'),
('2024-10-04', 'TRANSLATE-4219', 'bugfix', 'LanguageResources - Unable to add or remove client from default read/write in language resources', 'Fix customer assignment meta data update', '15'),
('2024-10-04', 'TRANSLATE-4216', 'bugfix', 'Import/Export - Across hotfolder: bconf causes import error', 'Fix bconf passing between plugins', '15'),
('2024-10-04', 'TRANSLATE-4205', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - Delayed worker leads to slow import for small tasks', 'Smaller tasks were running to long due delayed termtagger workers, this is fixed.', '15'),
('2024-10-04', 'TRANSLATE-4199', 'bugfix', 'file format settings - Import of BCONFs with corrupt Extension-mapping is possible (and maybe editing also)', 'FIX: It was possible to import a BCONF with faulty extension-mapping (only "." as extension)', '15'),
('2024-10-04', 'TRANSLATE-4194', 'bugfix', 'Configuration - make page number in system log readable', 'FIXED: page number was clipped if due to insufficient input field width within paging toolbar', '15'),
('2024-10-04', 'TRANSLATE-4193', 'bugfix', 'OpenTM2 integration - T5Memory import memory split does not work', 'Fix large TMX files import into t5memory', '15'),
('2024-10-04', 'TRANSLATE-4189', 'bugfix', 'Content Protection - html escaped in UI', 'Updated addQTip in TaskGrid.js and contentRecognition\'s GridController to retain linebreak tags', '15'),
('2024-10-04', 'TRANSLATE-4163', 'bugfix', 'Auto-QA - Terminology panel does not show the correct terminology when using "CTRL + ENTER" to save', 'FIX: wrong terminology shown when segment saved with "CTRL + ENTER"', '15'),
('2024-10-04', 'TRANSLATE-4056', 'bugfix', 'TermTagger integration - Delayed Workers: Improve Termtagging & Spellchecking to not stop when Containers are busy', '7.10.0: Enhancement: When a single Segment have a TermTagger error in the Import, a warning is reported to the task-events instead of an exception rendering the task erroneous
7.11.0: Fix performance problem with smaller task', '15');