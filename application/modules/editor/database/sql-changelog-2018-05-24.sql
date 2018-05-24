
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2018-05-24', 'TRANSLATE-1135', 'feature', 'Segment Grid: Highlight and Copy text in source and target columns', 'Segment Grid: Text in the segment grid cells can now be highlighted and copied from the source and target columns.', '14'),
('2018-05-24', 'TRANSLATE-1267', 'bugfix', 'TrackChanges: on export more content as only the content between DEL tags is getting deleted on some circumstances', 'TrackChanges: on export more content as only the content between DEL tags is getting deleted on some circumstances', '12'),
('2018-05-24', 'VISUAL-33', 'bugfix', 'VisualReview: Very huge VisualReview projects could not be imported', 'Very huge VisualReview projects lead to preg errors in PHP postprocessing of generated HTML, and could not be imported therefore.', '12'),
('2018-05-24', 'TRANSLATE-1102', 'bugfix', 'The user was logged out randomly in very seldom circumstances', 'It could happen (mainly with IE) that a user was logged out by loosing his session. This was happening when a default module page (like 404) was called in between other AJAX requests.', '8'),
('2018-05-24', 'TRANSLATE-1226', 'bugfix', 'fixed a Zend_Exception with message Array to string conversion', 'fixed a Zend_Exception with message Array to string conversion', '8');