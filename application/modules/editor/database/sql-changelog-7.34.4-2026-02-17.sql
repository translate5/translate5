
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2026-02-17', 'TRANSLATE-4834', 'feature', 'translate5 AI - Translation Quality Estimation (TQE): base implementation', '7.34.4: Added log entry when all segment views were dropped
7.34.2: DB migration file checks to prevent previously fixed DB problems in future
7.34.1: Fix for installations without AI plug-in
7.34.0: Translation Quality Estimation (TQE) is now possible with translate5 AI.', '15'),
('2026-02-17', 'TRANSLATE-5288', 'change', 'translate5 AI - Filters for TQE and TQE reasoning columns in editor', 'UI filters for TQE columns.', '15'),
('2026-02-17', 'TRANSLATE-5287', 'change', 'translate5 AI - TQE reasoning should always be done in English', 'Added new TQE system prompt to force the reasoning only on English.', '15'),
('2026-02-17', 'TRANSLATE-5066', 'change', 'ConnectWorldserver - Private Plugin Connect Worldserver: bundle one WS project into one Translate5 task', '7.34.4: Private Plugin Connect Worldserver: bundle one WS project into one Translate5 project', '15'),
('2026-02-17', 'TRANSLATE-5300', 'bugfix', 'Content Protection - Conversion worker fails unexpectedly if language resource is deleted', 'Fix behaviour for Conversion worker in case if language resource is no longer exists', '15'),
('2026-02-17', 'TRANSLATE-5297', 'bugfix', 'Editor general - Segment view not on archive operation.', 'PHP error fixed', '15'),
('2026-02-17', 'TRANSLATE-5284', 'bugfix', 'Task Management - Match-rate filter in task overview advanced filters does not work', 'Technical error in UI led to non working match rate filter when using 0 as filter value', '15'),
('2026-02-17', 'TRANSLATE-5274', 'bugfix', 'InstantTranslate, User Management - user role send to humann revision should require role InstantTranslate', 'Fix auto set role for human revision.', '15'),
('2026-02-17', 'TRANSLATE-5264', 'bugfix', 'Editor general - Error customer not found fixed', 'PHP error fixed', '15'),
('2026-02-17', 'TRANSLATE-5126', 'bugfix', 'InstantTranslate - Instant translate: open task for editing leaves task locked', '7.34.4: Reactivated the fixed unlock on browser close functionality
7.32.10: Deactivated the buggy unlock on browser close functionality until fixes are ready
7.32.8: Task will be unlocked and the job will be closed when user closes the browser.', '15'),
('2026-02-17', 'TRANSLATE-5037', 'bugfix', 'Editor general - copying from editor will insert terminology markup', '7.34.4: Additional fix
7.34.3: improved detection of whether copying is done from opened richtext editor', '15');