
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2026-03-09', 'TRANSLATE-5341', 'change', 'Hotfolder Import, VisualReview / VisualTranslation - COTI: Import visual files from sub-folders', 'COTI Hotfolder: Import visual files from sub-folders', '15'),
('2026-03-09', 'TRANSLATE-5348', 'bugfix', 'Content Protection - Integer rule "default generic with NNBSP separator" faulty', 'Fix integer rules formatter', '15'),
('2026-03-09', 'TRANSLATE-5345', 'bugfix', 'Content Protection - Converse memory worker is broken', 'Fix ConverseMemoryWorker', '15'),
('2026-03-09', 'TRANSLATE-5332', 'bugfix', 'Editor general - Insert from source produces error', 'When editing a segment and using ctrl a to select all then ctrl . to copy source content did lead to an error.', '15'),
('2026-03-09', 'TRANSLATE-5322', 'bugfix', 'Editor general - Statistics DB is locked', 'PHP error about locked statistics DB is fixed - pre-release to test the fix', '15'),
('2026-03-09', 'TRANSLATE-5307', 'bugfix', 'Hotfolder Import - COTI: ZIP contains Windows-style backslashes stored as literal characters', 'COTI: Improve handling of ZIP that contains Windows-style backslashes stored as literal characters', '15');