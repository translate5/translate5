
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-03-26', 'TRANSLATE-4361', 'feature', 'Import/Export - Add filters to html-report', '7.21.1: Making the default configurations work
7.21.0: Task HTML Export: JS UI filters implemented', '15'),
('2025-03-26', 'TRANSLATE-4567', 'change', 'LanguageResources - add tooltipp for penalties in fuzzy match panel', 'Improved tooltip for matchrate in editors\' matches panel ', '15'),
('2025-03-26', 'TRANSLATE-4571', 'bugfix', 'Package Ex and Re-Import - Translator package can not be re-imported on older tasks', 'Translator package could sometimes not be re-imported in older tasks.', '15'),
('2025-03-26', 'TRANSLATE-4570', 'bugfix', 'openai - Prevent OpenAI-specific CSS styles from affecting event log', 'FIXED: OpenAI-specific styles are not affecting other parts of application anymore', '15'),
('2025-03-26', 'TRANSLATE-4566', 'bugfix', 'Authentication, Task Management - Opening tasks via URL as read-only user produces an error', 'Opening read-only tasks via URL leads to a no access exception.', '15'),
('2025-03-26', 'TRANSLATE-4563', 'bugfix', 'job coordinator - job coordinator cannot open (in edit mode) job assigned to job coordinator colleage', 'Job Coordinator: allow not assigned coordinator enter a task for edit', '15'),
('2025-03-26', 'TRANSLATE-4562', 'bugfix', 'job coordinator - not all available Job Coordinators selectable in Job assignment dropdown', 'Job assignments: Fix Coordinator list in existing job', '15'),
('2025-03-26', 'TRANSLATE-4559', 'bugfix', 'Editor general - Create CLI command for optional sqlite initialisation', 'Change how optional sqlite DB is initialised in order to prevent problems.', '15'),
('2025-03-26', 'TRANSLATE-4556', 'bugfix', 't5memory - TM-Maintenance serverside issue: Total of found segments higher than actually displayed number of segments', 'Fix loading segments in TM Mainteenance for split memories', '15'),
('2025-03-26', 'TRANSLATE-4541', 'bugfix', 't5memory - t5memory: create next memory if getting error that current filename already exists', 't5memory: create next memory if getting error that current filename already exists', '15');