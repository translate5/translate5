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

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) VALUES ('runtimeOptions.requestLogging', '1', 'editor', 'editor', '0', '0', '', 'boolean', 'log some important requests for workflow handling');

CREATE TABLE `LEK_request_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) NOT NULL,
  `method` varchar(60) NOT NULL,
  `requestUri` varchar(512) DEFAULT NULL,
  `parameters` longtext DEFAULT NULL,
  `authUserGuid` varchar(38) NOT NULL,
  `authUserLogin` varchar(255) NOT NULL,
  `authUserName` varchar(512) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `taskGuid` (`taskGuid`),
  CONSTRAINT FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `LEK_task_log` ADD COLUMN `message` varchar(512) NOT NULL;
