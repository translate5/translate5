
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-08-19', 'TRANSLATE-4892', 'bugfix', 'Editor general - Cursor at the wrong position when deleting a character', 'Fixed cursor position when using a DEL key', '15'),
('2025-08-19', 'TRANSLATE-4891', 'bugfix', 'Editor general - F3 hotkey doesn\'t work', 'Fixed F3 hotkey', '15'),
('2025-08-19', 'TRANSLATE-4890', 'bugfix', 'Editor general - Whitespace tags are removed on segment save', 'Fixed saving segment containing whitespace tags', '15'),
('2025-08-19', 'TRANSLATE-4888', 'bugfix', 'Editor general - Applying a match from match panel does not work', 'Fixed CTRL+digit hotkey for applying match from matches panel', '15'),
('2025-08-19', 'TRANSLATE-4882', 'bugfix', 'Editor general - New segment editor largely hides next segment in case of length check of char count actively shown', 'FIXED: currently edited segment row height now fits the html-editor height to prevent overlapping the next segment row', '15'),
('2025-08-19', 'TRANSLATE-4870', 'bugfix', 'Editor general - HOTFIX: Frequently/rarely, Segments are not saved /saved with empty content', 'Added logging for segment save.', '15');