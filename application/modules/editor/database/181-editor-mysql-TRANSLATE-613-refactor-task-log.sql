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

ALTER TABLE `LEK_task` ADD COLUMN
`modified` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
AFTER `entityVersion`;

UPDATE `LEK_task` t, (
    SELECT it.`taskGuid`, ifnull(created, '2012-01-01') created
    FROM `LEK_task` it
    LEFT JOIN (
        SELECT `taskGuid`, max(`created`) `created`
        FROM `LEK_task_log`
        GROUP BY `taskGuid`
    ) ilog ON it.`taskGuid` = ilog.`taskGuid`
) log
SET t.modified = log.created
WHERE t.taskGuid = log.taskGuid;

INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('editor', 'admin', 'frontend', 'editorLogTask');
INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('editor', 'pm', 'frontend', 'editorLogTask');

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) 
VALUES ('runtimeOptions.errorCodesUrl', '1', 'app', 'system', 'https://confluence.translate5.net/display/TAD/ErrorCodes#ErrorCodes-{0}', 'https://confluence.translate5.net/display/TAD/ErrorCodes#ErrorCodes-{0}', '', 'string', 'Url for information to the error codes. The placeholder "{0}" will be replaced by the error code.');

