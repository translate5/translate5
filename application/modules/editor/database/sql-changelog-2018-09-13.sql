
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2018-09-13', 'TRANSLATE-1425', 'feature', 'Provide ImportArchiv.zip as download from the export menu for admin users', 'Provide ImportArchiv.zip as download from the export menu for admin users', '8'),
('2018-09-13', 'TRANSLATE-1426', 'bugfix', 'Segment length calculation was not working due not updated metaCache', 'The segment length calculation was not working due to a not updated metaCache', '12'),
('2018-09-13', 'TRANSLATE-1370', 'bugfix', 'Xliff Import can not deal with empty source targets as single tags', 'Xliff Import can not deal with empty source targets as single tags', '12'),
('2018-09-13', 'TRANSLATE-1427', 'bugfix', 'Date calculation in Notification Mails is wrong', 'The date calculation in Notification Mails was wrong', '14'),
('2018-09-13', 'TRANSLATE-1177', 'bugfix', 'Clicking into empty area of file tree produces sometimes an JS error', 'Clicking into empty area of file tree produces sometimes an JS error', '14'),
('2018-09-13', 'TRANSLATE-1422', 'bugfix', 'Uncaught TypeError: Cannot read property \'record\' of undefined', 'The following error in the frontend was fixed: Uncaught TypeError: Cannot read property \'record\' of undefined', '14');