
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2019-04-17', 'VISUAL-63', 'feature', 'VisualReview for translation tasks', 'VisualReview can now also be used for translation tasks.', '12'),
('2019-04-17', 'TRANSLATE-355', 'feature', 'Better error handling and user communication on import and export errors', 'Im and Export errors - in general all errors occuring in the context of a task - can now be investigated in the frontend.', '12'),
('2019-04-17', 'TRANSLATE-702', 'change', 'Migrate translate5 to be using PHP 7.3', 'Translate5 runs now with PHP 7.3 only', '8'),
('2019-04-17', 'TRANSLATE-613', 'change', 'Refactor error messages and error handling', 'The internal error handling in translate5 was completly changed', '8'),
('2019-04-17', 'TRANSLATE-293', 'change', 'create separate config for error mails receiver', 'Due several filter settings the receiver of error mails could be better configured.', '8'),
('2019-04-17', 'TRANSLATE-1605', 'bugfix', 'TrackChanges splits up the words send to the languagetool', 'Sometimes TrackChanges splits up the words send to the languagetool', '14'),
('2019-04-17', 'TRANSLATE-1624', 'bugfix', 'TrackChanges: not all typed characters are marked as inserted in special cases. ', 'If taking over a language resource match, select that content then with CTRL+A (select all), then type new characters: in that case not all characters are highlighted as inserted.', '14'),
('2019-04-17', 'TRANSLATE-1256', 'bugfix', 'In the editor CTRL-Z (undo) does not work after pasting content', 'CTRL-Z does now also undo pasted content', '14'),
('2019-04-17', 'TRANSLATE-1356', 'bugfix', 'In the editor the caret is placed wrong after CTRL+Z', 'The input caret was placed at a wrong place after undoing previously edited content', '14'),
('2019-04-17', 'TRANSLATE-1520', 'bugfix', 'Last CTRL+Z "loses" the caret in the Edtior', 'On using CTRL+Z (undo) it could happen that the input caret in the editor disappeared.', '14');