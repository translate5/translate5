-- /*
-- START LICENSE AND COPYRIGHT
-- 
--  This file is part of translate5
--  
--  Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
-- 
--  Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
-- 
--  This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
--  as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
--  included in the packaging of this file.  Please review the following information 
--  to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
--  http://www.gnu.org/licenses/agpl.html
-- 
--  There is a plugin exception available for use with this release of translate5 for
--  open source applications that are distributed under a license other than AGPL:
--  Please see Open Source License Exception for Development of Plugins for translate5
--  http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
--  folder of translate5.
--   
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
-- 			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
-- 
-- END LICENSE AND COPYRIGHT
-- */
CREATE TABLE `translate5`.`LEK_change_log` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `dateOfChange` DATE NULL,
  `jiraNumber` VARCHAR(100) NULL,
  `title` VARCHAR(50) NULL,
  `description` MEDIUMTEXT NULL,
  `userGroup` INT NULL,
  PRIMARY KEY (`id`));


CREATE TABLE `translate5`.`LEK_user_changelog_info` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `userId` INT NULL,
  `changelogId` INT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_LEK_user_changelog_info_1_idx` (`changelogId` ASC),
  CONSTRAINT `fk_LEK_user_changelog_info_1`
    FOREIGN KEY (`changelogId`)
    REFERENCES `translate5`.`LEK_change_log` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION);
