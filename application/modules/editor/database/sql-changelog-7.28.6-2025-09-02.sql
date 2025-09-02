
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-09-02', 'TRANSLATE-4940', 'change', 't5memory - Add error 504 and 512 to reorganize errors list', 'Added errors 504 and 512 to the list of errors that should trigger t5memory memory automatic reorganize', '15'),
('2025-09-02', 'TRANSLATE-4937', 'bugfix', 'Editor general - Cursor is on wrong position in case segment has spellcheck but no trackchanges', '[🐞 Fix] of the issue which may causing cursor to appear on the left of the typed letter in case segment has spellcheck markup and no trackchanges', '15'),
('2025-09-02', 'TRANSLATE-4936', 'bugfix', 'Export - Deleted content present in export', '[🐞 Fix] Track changes not completely removed on export when del-tag contained only singular internal tag', '15'),
('2025-09-02', 'TRANSLATE-4935', 'bugfix', 'Editor general - Error when applying match to editor', '[🐞 Fix] Applying a match from match panel to the editor produces error.', '15'),
('2025-09-02', 'TRANSLATE-4933', 'bugfix', 'Editor general - Inserting tags works wrong', '[🐞 Fix] Corrected detecting tag pairs when inserting a tag to the segment with a CTRL+, shortcut', '15'),
('2025-09-02', 'TRANSLATE-4932', 'bugfix', 'User Management - Custom Plugin: correct Plunet status-calculation for task', '[🐞 Fix] Set TUA-state "edit" equal to "open" for calculation, so state will be calculated correct as "in Arbeit"', '15'),
('2025-09-02', 'TRANSLATE-4928', 'bugfix', 'VisualReview / VisualTranslation - Visual: Reduce waiting-time until Visual export will be cleaned/deleted', '[🐞 Fix] Reduce time, an visual-export will not be recreated immediately to 30 sec.', '15'),
('2025-09-02', 'TRANSLATE-4926', 'bugfix', 'Editor general - when using backspace on a tracked deletion cursor will jump', '[🐞 Fix] Cursor position when typing with trackchanges is stable now and don\'t jump over the segment.', '15');