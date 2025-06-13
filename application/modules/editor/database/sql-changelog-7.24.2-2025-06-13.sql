
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-06-13', 'TRANSLATE-4705', 'change', 'Import/Export - Inability to export task with a special chars sequence in source segment', 'Fix inability to export tasks with some chars sequences in source segments', '15'),
('2025-06-13', 'TRANSLATE-4703', 'change', 'InstantTranslate - Buttons "Zur menschlichen Revision senden/ In menschlicher Revision"', '* InstantTranslate: Improve visibility of buttons', '15'),
('2025-06-13', 'TRANSLATE-4689', 'change', 't5memory - Change batch deletion to one-by-one for up to 5000 segments', 'TMMaintenance: Increased amount of segments that are deleted one-by-one in batch deletion to 5000.', '15'),
('2025-06-13', 'TRANSLATE-4660', 'change', 'InstantTranslate - Send to Human Revision: Doesn\'t show client user', 'Added user who send file translation to human revision to PM\'s email notification', '15'),
('2025-06-13', 'TRANSLATE-4716', 'bugfix', 'Task Management - Job sorter is not applied when adding new job in task user association panel', 'Job sorting by workflow now will be applied when new job is added or removed from the task jobs list.', '15'),
('2025-06-13', 'TRANSLATE-4711', 'bugfix', 'Client management - ClientPM\'s attempt to assign client to language resource result in in invalid client list', 'Fix Client PM management of clients in language resource', '15'),
('2025-06-13', 'TRANSLATE-4707', 'bugfix', 'TM Maintenance - Tags validation works in TMMaintenance', 'Fixed tags validation in TM Maintenance', '15'),
('2025-06-13', 'TRANSLATE-4690', 'bugfix', 'VisualReview / VisualTranslation - JavaScript Error in Visual TextReflow-Converter', 'Fixed JavaScript-error in Visual\'s text-Reflow converter', '15');