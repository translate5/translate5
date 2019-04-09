
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2019-02-07', 'TRANSLATE-1570', 'bugfix', 'Editor-only usage (embedded translate5) was not working properly due JS errors', 'If translate5 was used in the editor-only mode (embedded translate5 editor in different management software) there were some errors in the GUI.', '8'),
('2019-02-07', 'TRANSLATE-1548', 'bugfix', 'TrackChanges: always clean up nested DEL tags in the frontend', 'Sometimes it happend, that the Editor was sending nested DEL tags to the server, this was producing an error on server side.', '12'),
('2019-02-07', 'TRANSLATE-1526', 'bugfix', 'TrackChanges: pasting content into the editor could lead to an JS error ', 'The error happend when the segment content was selected via double click before the content was pasted.', '14'),
('2019-02-07', 'TRANSLATE-1566', 'bugfix', 'Segment pixel length restriction does not work with globalese pretranslation', 'The segment pixel length restriction was not working if using globalese pretranslation.', '12'),
('2019-02-07', 'TRANSLATE-1556', 'bugfix', 'pressing ctrl-c in language resource panel produced an JS error in the GUI', 'Using ctrl-c to copy content from the language resource panel produced an error in the GUI.', '14'),
('2019-02-07', 'TRANSLATE-910', 'bugfix', 'Fast clicking on segment bookmark button produces an error on server side', 'Multiple fast clicking on segment bookmark button was leading to an error on server side.', '14'),
('2019-02-07', 'TRANSLATE-1545', 'bugfix', 'TermPortal: Term details are not displayed in term portal', 'After term collection import, the terms attributes and term entry attributes were not listed when the term was clicked in the term portal.', '12'),
('2019-02-07', 'TRANSLATE-1525', 'bugfix', 'TrackChanges: seldom error in the GUI fixed', 'The error was: Failed to execute \'setStartBefore\' on \'Range\': the given Node has no parent.', '12'),
('2019-02-07', 'TRANSLATE-1230', 'bugfix', 'Translate5 was not usable on touch devices', 'The problem was caused by the used ExtJS library.', '14');