
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2018-03-15', 'T5DEV-213', 'feature', 'Special Plugin for attaching Translate5 to across', 'This plugin sets the trans-units translate attribute in the XLF export depending on the autostate. If a segment was changed or a comment was added it is set to translatete=yes otherwise to no. Needed in special workflows together with across.', '8'),
('2018-03-15', 'TRANSLATE-1180', 'change', 'Improve logging and enduser communication in case of ZfExtended_NoAccessException exceptions', 'Improve logging and enduser communication in case of ZfExtended_NoAccessException exceptions', '12'),
('2018-03-15', 'TRANSLATE-1179', 'change', 'HTTP HEAD and OPTIONS request should not create a log entry', 'HTTP HEAD and OPTIONS request should not create a log entry', '8');