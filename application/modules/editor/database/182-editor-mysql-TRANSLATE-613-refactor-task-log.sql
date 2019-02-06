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


ALTER TABLE `LEK_workflow_log` 
CHANGE COLUMN `userGuid` `authUserGuid` varchar(38) NOT NULL,
ADD COLUMN `taskState` varchar(60) NOT NULL AFTER `taskGuid`,
ADD COLUMN `authUser` varchar(512) NOT NULL AFTER `authUserGuid`,
ADD COLUMN `userGuid` varchar(38) DEFAULT NULL AFTER `authUser`,
ADD COLUMN `user` varchar(512) DEFAULT NULL AFTER `userGuid`;
 
INSERT INTO `LEK_workflow_log` (`taskGuid`, `authUserGuid`, `authUser`, `userGuid`, `user`, `taskState`, `created`)
SELECT `taskGuid`, `authUserGuid`, CONCAT(`authUserLogin`, ' (', `authUserName`, ')'), `userGuid`, CONCAT(`userLogin`, ' (', `userName`, ')'), `state`, `created`
FROM `LEK_task_log`;

DROP TABLE `LEK_task_log`;

CREATE TABLE `LEK_task_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) NOT NULL,
  `level` tinyint(2) NOT NULL DEFAULT 4,
  `state` varchar(60) NOT NULL,
  `eventCode` varchar(10) NOT NULL,
  `domain` varchar(128) NOT NULL,
  `worker` varchar(128) NULL,
  `message` varchar(1024) NOT NULL,
  `authUserGuid` varchar(38) NULL,
  `authUser` varchar(512) NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `taskGuid` (`taskGuid`),
  CONSTRAINT `LEK_task_log_ibfk_1` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
