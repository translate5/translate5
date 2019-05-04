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


# add system-wide config-default
INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) 
VALUES ('runtimeOptions.customers.anonymizeUsers', '1', 'editor', 'metadata', '1', '0', '', 'boolean', 'Are the users per default to be anonymized for customers? (Can be overwritten in LEK_customerConfiguration.)');

# workaround for new attribute on customer level: Anonymize users in workflow
# (= will later be handled via config-tables)
ALTER TABLE `LEK_customer`ADD COLUMN `anonymizeUsers` TINYINT(0) NOT NULL DEFAULT 0;

# add table for tracking the order of users who opened a task (and their name and role)
CREATE TABLE IF NOT EXISTS `LEK_taskUserTracking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskGuid` VARCHAR(38) NOT NULL,
  `userGuid` VARCHAR(38) NOT NULL,
  `taskOpenerNumber` INT(3) NOT NULL,
  `firstName` VARCHAR(255) NOT NULL,
  `surName` VARCHAR(255) NOT NULL,
  `userName` VARCHAR(255) NOT NULL,
  `role` VARCHAR(60) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
ALTER TABLE `LEK_taskUserTracking` ADD UNIQUE(`taskGuid`,`userGuid`);
ALTER TABLE `LEK_taskUserTracking` ADD FOREIGN KEY (`taskGuid` ) REFERENCES `LEK_task` (`taskGuid` ) ON DELETE CASCADE;
ALTER TABLE `LEK_taskUserTracking` ADD FOREIGN KEY (`userGuid` ) REFERENCES `Zf_users` (`userGuid` ) ON DELETE CASCADE;

# permission for new taskusertrackings-store
INSERT INTO Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES 
('editor', 'editor', 'editor_taskusertracking', 'all');
