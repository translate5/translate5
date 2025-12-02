
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-12-01', 'TRANSLATE-4797', 'feature', 'Export - Add batch operation for task export "original format, translated/reviewed"', '7.32.6: Tests fixed
7.32.5: Test fix related to worker problems
7.30.2: Improved queueing of batch export workers
7.30.0: Added batch operation for task export "original format, translated/reviewed"
', '15'),
('2025-12-01', 'TRANSLATE-4099', 'change', 'LanguageResources - Update DeepL SDK recurring issue', 'Internal update of the DeepL SDK (Internal API)', '15'),
('2025-12-01', 'TRANSLATE-5145', 'bugfix', 'Task Management - autoclose not triggered for unconfirmed tasks', 'Include unconfirmed tasks in auto-close job evaluation.', '15'),
('2025-12-01', 'TRANSLATE-5144', 'bugfix', 'Repetition editor - Wrong repetition hash calculation', 'Fix hash calculation of repetitions', '15'),
('2025-12-01', 'TRANSLATE-4579', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - Worker queue is called frequently and chained', '7.32.6: small fix for tests
7.32.2: set processdaemon as new default worker trigger
7.21.3: Performance improvement for the workers, prevention of system overload when scheduling thousands of workers.', '15');