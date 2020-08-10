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

ALTER TABLE `LEK_task` 
ADD COLUMN `workflowStepName` VARCHAR(60) NOT NULL DEFAULT '' AFTER `workflowStep`;

ALTER TABLE `LEK_task` 
ADD COLUMN `foreignId` VARCHAR(120) NOT NULL DEFAULT '' 
COMMENT 'Used as optional reference field for Tasks create vi API' 
AFTER `taskNr`;

ALTER TABLE `LEK_task` 
ADD COLUMN `foreignName` VARCHAR(255) NOT NULL DEFAULT '' 
COMMENT 'Used as optional reference field for Tasks create vi API' 
AFTER `taskName`;

UPDATE `LEK_task` as `t`,
`LEK_workflow_log` as `log`,
(
    SELECT MAX(`id`) as `id`
    FROM `LEK_workflow_log`
    GROUP BY taskGuid
) `lastLog`
SET `t`.`workflowStepName` = `log`.`stepName`
WHERE `lastLog`.`id` = `log`.`id` AND `t`.`taskGuid` = `log`.`taskGuid`;
