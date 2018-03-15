
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2017-11-30', 'TRANSLATE-935', 'feature', 'Configure columns of task overview on system level', 'The position and visibility of grid columns in the task overview can be predefined.', '14'),
('2017-11-30', 'TRANSLATE-905', 'change', 'Improve formatting of the maintenance mode message and add timezone to the timestamp.', 'Improve formatting of the maintenance mode message and add timezone to the timestamp.', '14'),
('2017-11-30', 'T5DEV-198', 'bugfix', 'Fixes for the non public VisualReview Plug-In', 'Bugfixes for VisualReview. This plug-in turns translate5 into a visual review editor. The Plug-In is not publically available!', '12'),
('2017-11-30', 'TRANSLATE-1063', 'bugfix', 'VisualReview Plug-In: missing CSS for internal tags and to much line breaks', 'In the VisualReview some optical changes were made for the mouse over effect.', '14'),
('2017-11-30', 'TRANSLATE-1053', 'bugfix', 'Repetition editor starts over tag check dialog on overtaking segments from MatchResource', 'On saving a segment taken over from a match resource and having a incorrect internal tag structure the repetition editor dialog was overlapping the tag check dialog.', '12');
