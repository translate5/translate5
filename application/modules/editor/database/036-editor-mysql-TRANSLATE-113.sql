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

CREATE TABLE IF NOT EXISTS `LEK_workflow_userpref` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) NOT NULL COMMENT 'Foreign Key to LEK_task',
  `workflow` varchar(60) NOT NULL COMMENT 'links to the used workflow for this ',
  `workflowStep` varchar(60) DEFAULT NULL COMMENT 'the workflow step which is affected by the settings, optional, null to affect all steps',
  `anonymousCols` tinyint(1) DEFAULT 0 NOT NULL COMMENT 'should the column names be rendered anonymously',
  `visibility` enum('show', 'hide', 'disable') DEFAULT 'show' COMMENT 'visibility of non-editable target columns',
  `userGuid` varchar(38) DEFAULT NULL COMMENT 'Foreign Key to Zf_users, optional, constrain the prefs to this user',
  `fields` varchar(300) NOT NULL COMMENT 'field names as used in LEK_segment_fields',
  PRIMARY KEY (`id`),
  CONSTRAINT FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE,
  CONSTRAINT FOREIGN KEY (`taskGuid`, `userGuid`) REFERENCES `LEK_taskUserAssoc` (`taskGuid`, `userGuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `LEK_workflow_userpref` (`taskGuid`, `workflow`, `fields`) 
select taskGuid, 'default', GROUP_CONCAT(name ORDER BY id SEPARATOR ',') from LEK_segment_field GROUP BY taskGuid;

ALTER TABLE `LEK_task` ADD COLUMN `workflow` VARCHAR(60) NOT NULL DEFAULT 'default' AFTER `state`;