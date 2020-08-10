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

CREATE TABLE `LEK_file_filter_dump` AS SELECT * FROM `LEK_file_filter`;

DROP TABLE `LEK_file_filter`;

CREATE TABLE `LEK_file_filter` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `fileId` INT NOT NULL,
  `type` VARCHAR(45) NOT NULL DEFAULT 'import',
  `filter` VARCHAR(160) NOT NULL,
  `parameters` LONGTEXT NULL,
  `taskGuid` VARCHAR(38) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `index2` (`fileId` ASC, `taskGuid` ASC, `type` ASC),
  CONSTRAINT `fk_LEK_file_filter_1`
    FOREIGN KEY (`taskGuid`)
    REFERENCES `LEK_task` (`taskGuid`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_LEK_file_filter_2`
    FOREIGN KEY (`fileId`)
    REFERENCES `LEK_files` (`id`)
    ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `LEK_file_filter` SELECT * FROM `LEK_file_filter_dump` WHERE `taskGuid` IN (SELECT `taskGuid` FROM `LEK_task`);

DROP TABLE `LEK_file_filter_dump`;
