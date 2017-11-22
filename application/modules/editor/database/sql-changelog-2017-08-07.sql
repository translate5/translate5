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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2017-08-07', 'TRANSLATE-925', 'feature', 'Support xliff 1.2 as import format', 'See: http://docs.oasis-open.org/xliff/v1.2/os/xliff-core.html#Struct_Segmentation', '12'),
('2017-08-07', 'T5DEV-172', 'change', 'Ext 6.2 update prework: Quicktip manager instances have problems if configured targets does not exist anymore', 'Preparation for the upcoming update to ExtJS 6.2', '8'),
('2017-08-07', 'T5DEV-171', 'change', 'Ext 6.2 update prework: Get Controller instance getController works only with full classname', 'Preparation for the upcoming update to ExtJS 6.2', '8'),
('2017-08-07', 'TRANSLATE-953', 'bugfix', 'Direct Workers (like GUI TermTagging) are using the wrong worker state', 'This problem was leading only seldom to errors.', '8');