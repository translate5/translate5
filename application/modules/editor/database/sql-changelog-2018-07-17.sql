
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2018-07-17', 'TRANSLATE-1349', 'change', 'Remove the message of saving a segment successfully', 'Remove the message of saving a segment successfully', '14'),
('2018-07-17', 'TRANSLATE-1337', 'bugfix', 'removing orphaned tags is not working with tag check save anyway', 'removing orphaned tags is not working with tag check save anyway', '14'),
('2018-07-17', 'TRANSLATE-1245', 'bugfix', 'Add missing keyboard shortcuts and other smaller fixes related to segment commenting', 'Add missing keyboard shortcuts and other smaller fixes related to segment commenting', '14'),
('2018-07-17', 'TRANSLATE-1326', 'bugfix', 'VisualReview: Enable Comments for non-editable segment in visualReview mode and normal mode', 'Via ACL it can be enabled that non-editable segment can be commented in normal and in visualReview mode', '14'),
('2018-07-17', 'TRANSLATE-1345', 'bugfix', 'Unable to import task with Relais language and terminology', 'Unable to import task with Relais language and terminology', '12'),
('2018-07-17', 'TRANSLATE-1347', 'bugfix', 'Unknown Term status are not set to the default as configured', 'Unknown Term stats are now set again to the configured default value    ', '12'),
('2018-07-17', 'TRANSLATE-1351', 'bugfix', 'Remove jquery from official release and bundle it as dependency', 'Remove jquery from official release and bundle it as dependency', '8'),
('2018-07-17', 'TRANSLATE-1353', 'bugfix', 'Huge TBX files can not be imported', 'Huge TBX files can not be imported', '12');