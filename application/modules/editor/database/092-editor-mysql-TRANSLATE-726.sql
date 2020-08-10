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

ALTER TABLE `LEK_change_log` 
ADD COLUMN `type` VARCHAR(45) NULL DEFAULT 'change' AFTER `userGroup`;

UPDATE `LEK_change_log` SET `type` = 'bugfix' WHERE jiraNumber in (
'TRANSLATE-725','TRANSLATE-727','TRANSLATE-728','several','TRANSLATE-715','TRANSLATE-687','TRANSLATE-689','TRANSLATE-705','TRANSLATE-710','TRANSLATE-713'
);
UPDATE `LEK_change_log` SET `type` = 'feature' WHERE jiraNumber in (
'TRANSLATE-637','TRANSLATE-137','TRANSLATE-680','TRANSLATE-612','TRANSLATE-664','TRANSLATE-684','TRANSLATE-644','TRANSLATE-625','TRANSLATE-621','TRANSLATE-707','TRANSLATE-138','TRANSLATE-718'
);
UPDATE `LEK_change_log` SET `type` = 'change' WHERE jiraNumber in (
'TRANSLATE-646','TRANSLATE-724','TRANSLATE-508','TRANSLATE-706'
);
