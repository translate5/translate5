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

CREATE TABLE IF NOT EXISTS `LEK_taskUserAssoc` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) NOT NULL,
  `userGuid` varchar(38) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- the following with alter statements to reuse existing LEK_taskUserAssoc tables
ALTER TABLE `LEK_taskUserAssoc` ADD COLUMN `state` VARCHAR(60) NOT NULL DEFAULT 'open';
ALTER TABLE `LEK_taskUserAssoc` ADD COLUMN `role` VARCHAR(60) NOT NULL DEFAULT 'lector';

SET foreign_key_checks = 0;

ALTER TABLE `LEK_taskUserAssoc` ADD UNIQUE(`taskGuid`,`userGuid`,`role`);
ALTER TABLE `LEK_taskUserAssoc` ADD FOREIGN KEY (`taskGuid` ) REFERENCES `LEK_task` (`taskGuid` ) ON DELETE CASCADE;
ALTER TABLE `LEK_taskUserAssoc` ADD FOREIGN KEY (`userGuid` ) REFERENCES `Zf_users` (`userGuid` ) ON DELETE CASCADE;

SET foreign_key_checks = 1;