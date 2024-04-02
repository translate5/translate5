
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2024-03-28', 'TRANSLATE-3837', 'change', 'Installation & Update - docker on premise: termtagger and languagetool healthcheck changed', 'docker compose pull to get the latest containers. For termtagger there is now a health check which forces the termtagger to restart when it consumes to much memory.', '15'),
('2024-03-28', 'TRANSLATE-3824', 'change', 'Installation & Update - Show hosting status in UI and create separate monitoring endpoint', 'Add a separate monitoring endpoint, add in hosting some information about the hosting status.', '15'),
('2024-03-28', 'TRANSLATE-3820', 'change', 'Task Management - Add tk-TM (Turkmen (Turkmenistan)) to translate5 languages', 'Add tk-TM (Turkmen (Turkmenistan)) to language list', '15'),
('2024-03-28', 'TRANSLATE-3815', 'change', 'MatchAnalysis & Pretranslation - Fix MatchAnalysisTest', 'Fixed test', '15'),
('2024-03-28', 'TRANSLATE-3814', 'change', 'Import/Export - FIX: Enable use of TMX zip archive in TM creation process', 'Fix translations and zip usage on TM creation process', '15'),
('2024-03-28', 'TRANSLATE-3832', 'bugfix', 'Editor general - RootCause error: Cannot read properties of null (reading \'expand\')', 'UI fixing a problem expanding the quality tree.', '15'),
('2024-03-28', 'TRANSLATE-3826', 'bugfix', 'Editor general - RootCause error: me.selectedCustomersConfigStore is null', 'Fix for a problem when opening the task creation window and closing it immediately.', '15'),
('2024-03-28', 'TRANSLATE-3825', 'bugfix', 'Editor general - No access exception: reopen locked task', 'Fix for a problem where task was unlocked by the inactive-cleanup component, but the user has still the translate5 task-editing UI open.', '15'),
('2024-03-28', 'TRANSLATE-3823', 'bugfix', 'TermPortal - Remove non breaking spaces from terms', 'Remove non breaking spaces and non regular white-spaces on term import and from all existing terms in the database.', '15'),
('2024-03-28', 'TRANSLATE-3821', 'bugfix', 'Export - Across Hotfoler: Export worker does not wait for Okapi worker', 'Fix Across Hotfolder tasks export', '15'),
('2024-03-28', 'TRANSLATE-3817', 'bugfix', 'InstantTranslate - translate5 sends unescaped xml special char via InstantTranslate to t5memory', 'Escape potentially unescaped content sent to t5memory since this may crashes t5memory', '15'),
('2024-03-28', 'TRANSLATE-3811', 'bugfix', 'VisualReview / VisualTranslation - Visual: Font may be mis-selected when one font\'s name is containing the other', 'FIX: Some visual fonts have been mis-identified as being identical', '15'),
('2024-03-28', 'TRANSLATE-3769', 'bugfix', 'Editor general - Cancel import unlocks exporting task', 'Fix for a problem with task cancel import logic.', '15'),
('2024-03-28', 'TRANSLATE-3643', 'bugfix', 'User Management - enable PM role to create MT resources', 'PM\'s are allowed to create MT resources.', '15');