
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2019-07-17', 'TRANSLATE-1489', 'feature', 'Export task as excel and be able to reimport it', 'While the task is exported as excel, it is locked for further editing until the changes are reimported.', '12'),
('2019-07-17', 'TRANSLATE-1464', 'bugfix', 'SpellCheck for Japanese and other languages using the Microsoft Input Method Editor', 'For such languages the spell check is currently triggered via a separate button.', '14'),
('2019-07-17', 'TRANSLATE-1715', 'bugfix', 'XLF Import: Segments with tags only should be ignored and pretranslated automatically on translation tasks', 'Since the source contains tags only, no request to a TM must be done. The target is filled with the tags directly.', '12'),
('2019-07-17', 'TRANSLATE-1705', 'bugfix', 'Pre-translation does not remove "AdditionalTag"-Tag from OpenTM2', 'Since it may happen, that TM matches may contain more or different tags, in the GUI this additional tags are shown. But for pre-translation these tags must be removed.', '12'),
('2019-07-17', 'TRANSLATE-1637', 'bugfix', 'MatchAnalysis: Errors in Frontend when analysing multiple tasks', 'This JS error is fixed now.', '12'),
('2019-07-17', 'TRANSLATE-1658', 'bugfix', 'Notify assoc users with state open in notifyOverdueTasks', 'All users were notified, also the ones which already finished the task.', '12'),
('2019-07-17', 'TRANSLATE-1709', 'bugfix', 'Missing translator checkers in email when all proofreaders are finished', 'The information about the following translator checkers was readded.', '12'),
('2019-07-17', 'TRANSLATE-1708', 'bugfix', 'Possible server error on segment search', 'This seldom error is fixed.', '14'),
('2019-07-17', 'TRANSLATE-1707', 'bugfix', 'XLIFF 2.1 Export creates invalid XML', 'Some special characters inside a XML comment was leading to invalid XML.', '12'),
('2019-07-17', 'TRANSLATE-1702', 'bugfix', 'Multiple parallel export of the same task from the same session leads to errors', 'Impatient users start multiple exports of the same task, this was leading to dead locks in the export.', '12'),
('2019-07-17', 'TRANSLATE-1706', 'bugfix', 'Improve TrackChanges markup for internal tags in Editor', 'Now a changed internal tag can be recognized in the editor.', '14');