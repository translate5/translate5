
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-01-24', 'TRANSLATE-4407', 'change', 'TrackChanges - Update text differ', 'Update text differ package', '15'),
('2025-01-24', 'TRANSLATE-4393', 'change', 'usability task overview - Add number of tasks affected in batch set warning', 'Added number of tasks affected for filter-based selection in batch set warning', '15'),
('2025-01-24', 'TRANSLATE-4390', 'change', 't5memory - Change reimport segments behaviour', 'Automatic task to t5memory reimport now saves all segments, but not only edited by user.', '15'),
('2025-01-24', 'TRANSLATE-4105', 'change', 'VisualReview / VisualTranslation - FIX several smaller quirks', 'IMPROVEMENT Visual: Fix several smaller quirks in the reflow-detection', '15'),
('2025-01-24', 'TRANSLATE-4396', 'bugfix', 'LanguageResources - Language resource import status check problem', 'FIXED: un-triggered import status check for added languageresources', '15'),
('2025-01-24', 'TRANSLATE-4395', 'bugfix', 'TM Maintenance - Missing check for field existence in TM Maintenance', 'TM Maintenance: Fix PHP Warning: Undefined array key "additionalInfo"', '15'),
('2025-01-24', 'TRANSLATE-4391', 'bugfix', 't5memory - Error occures if no segments for reimport', 'Fixed error that was occurring if there were no segments for reimport after task workflow is ended', '15'),
('2025-01-24', 'TRANSLATE-4388', 'bugfix', 'openai - OpenAI: Retrieving Terminology for OpenAI may crashes Termtagger', 'FIX: Retrieving segment terminology in OpenAI pretranslation may crashes termtagger', '15'),
('2025-01-24', 'TRANSLATE-4387', 'bugfix', 'LanguageResources - Rights problem for language resources to customers associations', 'FIXED: whole app contantly reloading for Editor-only users', '15'),
('2025-01-24', 'TRANSLATE-4385', 'bugfix', 'LanguageResources - DeepL plugin: Attempt to use glossary with language pair that not supports them', 'DeepL plugin: validate language pairs to ability to use glossaries', '15'),
('2025-01-24', 'TRANSLATE-4384', 'bugfix', 'Content Protection - wrong warning with TM conversion', 'Content protection: adjust warning message', '15'),
('2025-01-24', 'TRANSLATE-4383', 'bugfix', 'Hotfolder Import - Hotfolder will import projects from wrong folder', 'Hotfolder: download files from Import folder only', '15'),
('2025-01-24', 'TRANSLATE-4381', 'bugfix', 'TM Maintenance - Scrolling problem', 'FIXED: error popping on loading 2nd page of TMMaintenance search results', '15');