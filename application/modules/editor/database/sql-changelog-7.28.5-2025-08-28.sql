
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-08-28', 'TRANSLATE-4914', 'change', 'Editor general - F3 shortcut for concordance search does not work with new editor', 'Added missing F3 shortcut row editor\'s source column', '15'),
('2025-08-28', 'TRANSLATE-4927', 'bugfix', 'Editor general - Translation containing html is processed wrong', '[🐞 Fix] HTML-like content rendering in the editor now works correct without losing data', '15'),
('2025-08-28', 'TRANSLATE-4925', 'bugfix', 'Auto-QA - AutoQA: duplicated \'interactive\' flag added on segment edit', '[🐞 Fix] Prevent duplicate \'interactive\' flag from being added on segment edit', '15'),
('2025-08-28', 'TRANSLATE-4923', 'bugfix', 'Editor general - Single tags resolved incorrectly on segment save in editor', '[🐞 Fix] Fixed single tag handling in editor', '15'),
('2025-08-28', 'TRANSLATE-4919', 'bugfix', 'VisualReview / VisualTranslation - Fix potential JS error in the Visual', '[🐞 Fix] Potential JS-Error in Visual when segment-data is not properly set', '15'),
('2025-08-28', 'TRANSLATE-4916', 'bugfix', 'Editor general - New editor: Apostroph gets converted to HTML entity', '[🐞 Fix] Fixed bug which caused some special characters inside trackchanges to be shown as HTML encoded symbols', '15'),
('2025-08-28', 'TRANSLATE-4915', 'bugfix', 'Editor general - Length check with new editor ignores changes in line count', '[🐞 Fix] Fixed issue when deleted newline is shown by TrackChanges', '15'),
('2025-08-28', 'TRANSLATE-4913', 'bugfix', 'Editor general - New segment editor: Spaces get removed upon saving', '[🐞 Fix]  Fixed issue when changed whitespace processing in new editor may lead to disappearing blanks after saving a segment', '15'),
('2025-08-28', 'TRANSLATE-4912', 'bugfix', 'Editor general - New segment editor bug: Loss of whitespace and apostroph char on export', '[🐞 Fix]  Fixed issue when sequences of trackchanges deleting word-separating whitespace may lead to all whitespaces between two words being removed', '15'),
('2025-08-28', 'TRANSLATE-4907', 'bugfix', 'Editor general - RootCause: Cannot read properties of undefined (reading \'rowToEditOrigHeight\')', '[🐞 Fix]  Used correct way to reference row editor', '15');