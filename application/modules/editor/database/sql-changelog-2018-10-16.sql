
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2018-10-16', 'TRANSLATE-1433', 'feature', 'Trigger workflow actions also on removing a user from a task', 'Trigger workflow actions also on removing a user from a task', '12'),
('2018-10-16', 'VISUAL-26', 'feature', 'VisualReview: Add buttons to resize layout', 'Added some buttons to zoom into the visualized content in the upper visual review frame.', '14'),
('2018-10-16', 'TRANSLATE-1207', 'feature', 'Add buttons to resize/zoom segment table', 'Add buttons to resize/zoom segment table', '14'),
('2018-10-16', 'TRANSLATE-1135', 'feature', 'Highlight and Copy text in source and target columns', 'Highlight and Copy text in source and target columns', '14'),
('2018-10-16', 'TRANSLATE-1380', 'change', 'Change skeleton-files location from DB to filesystem', 'Change the location of the skeleton-files needed for the export from DB to filesystem', '8'),
('2018-10-16', 'TRANSLATE-1381', 'change', 'Print proper error message if SDLXLIFF with comments is imported', 'Print proper error message if SDLXLIFF with comments is imported', '8'),
('2018-10-16', 'TRANSLATE-1437', 'change', 'Collect relais file alignment errors instead mail and log each error separately', 'Collect relais file alignment errors instead mail and log each error separately', '8'),
('2018-10-16', 'TRANSLATE-1396', 'change', 'Remove the misleading "C:\fakepath\" from task name', 'Remove the misleading "C:\fakepath\" from task name', '12'),
('2018-10-16', 'TRANSLATE-1442', 'bugfix', 'Repetition editor replaces wrong tag if segment contains tags only', 'Repetition editor uses the wrong tag if the target of the segment to be replaced is empty and if the segment contains tags only. ', '14'),
('2018-10-16', 'TRANSLATE-1441', 'bugfix', 'Exception about missing segment materialized view on XLIFF2 export', 'Exception about missing segment materialized view on XLIFF2 export', '12'),
('2018-10-16', 'TRANSLATE-1382', 'bugfix', 'Deleting PM users associated to tasks can lead to workflow errors', 'Deleting PM users associated to tasks can lead to workflow errors', '12'),
('2018-10-16', 'TRANSLATE-1335', 'bugfix', 'Wrong segment sorting and filtering because of internal tags', 'Wrong segment sorting and filtering because of internal tags', '14'),
('2018-10-16', 'TRANSLATE-1129', 'bugfix', 'Missing segments on scrolling with page-down / page-up', 'Using page-up and page-down keys in the segment grid for scrolling was jumping over some segments so not all segments were visible to the user', '14'),
('2018-10-16', 'TRANSLATE-1431', 'bugfix', 'Deleting a comment can lead to a JS exception', 'Deleting a comment can lead to a JS exception', '14'),
('2018-10-16', 'VISUAL-55', 'bugfix', 'VisualReview: Replace special Whitespace-Chars', 'Replace special Whitespace-Chars to get more matches', '12'),
('2018-10-16', 'TRANSLATE-1438', 'bugfix', 'Okapi conversion did not work anymore due to Okapi Longhorn bug', 'Due to an Okapi Longhorn bug the conversion of native source files to xliff did not work any more with translate5. A workaround was implemented.', '12');