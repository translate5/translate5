
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2024-06-10', 'TRANSLATE-4000', 'feature', 'Editor general - Simple json editor for UI configs', 'New simple json editor in the UI for map configs.', '15'),
('2024-06-10', 'TRANSLATE-3923', 'change', 'Auto-QA - "Not found in target" category according to target term', 'translate5 - 7.5.0: Quality errors in \'Not found in target\' category group now count cases when best possible translations of source terms are not found in segment target
translate5 - 7.6.3: Improve tests', '15'),
('2024-06-10', 'TRANSLATE-3998', 'bugfix', 'Export - Wrong date values in excel export', 'Fixed wrong dates in excel export when the date time is 00:00:00', '15'),
('2024-06-10', 'TRANSLATE-2500', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - Worker Architecture: Solving Problems with Deadlocks and related Locking/Mutex Quirks', '5.2.2 Improved the internal worker handling regarding DB dead locks and a small opportunity that workers run twice.
7.5.0 Improved the setRunning condition to reduce duplicated worker runs
7.6.3 Improved worker queue for large project imports
', '15');