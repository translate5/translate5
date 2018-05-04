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

CREATE TABLE `LEK_term_collection` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(256) NULL,
  `customerId` INT NULL,
  `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `fk_LEK_term_collection_1_idx` (`customerId` ASC),
  CONSTRAINT `fk_LEK_term_collection_1`
    FOREIGN KEY (`customerId`)
    REFERENCES `LEK_customer` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE);


CREATE TABLE `LEK_term_entry` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `collectionId` INT NULL,
  `groupId` VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_LEK_term_entry_1_idx` (`collectionId` ASC),
  CONSTRAINT `fk_LEK_term_entry_1`
    FOREIGN KEY (`collectionId`)
    REFERENCES `LEK_term_collection` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE);

ALTER TABLE `LEK_terms` 
ADD COLUMN `collectionId` INT(11) NULL AFTER `tigId`,
ADD COLUMN `termEntryId` INT(11) NULL AFTER `collectionId`,
ADD INDEX `fk_LEK_terms_1_idx` (`termEntryId` ASC),
ADD INDEX `fk_LEK_terms_2_idx` (`collectionId` ASC);
ALTER TABLE `LEK_terms` 
ADD CONSTRAINT `fk_LEK_terms_1`
  FOREIGN KEY (`termEntryId`)
  REFERENCES `LEK_term_entry` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE,
ADD CONSTRAINT `fk_LEK_terms_2`
  FOREIGN KEY (`collectionId`)
  REFERENCES `LEK_term_collection` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;



CREATE TABLE `LEK_term_attributes_label` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `label` VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `id_UNIQUE` (`id` ASC));


CREATE TABLE `LEK_term_attributes` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `labelId` INT NULL,
  `collectionId` INT NULL,
  `termId` INT NULL,
  `parentId` INT NULL DEFAULT NULL,
  `language` VARCHAR(45) NULL DEFAULT NULL,
  `name` VARCHAR(45) NULL,
  `attrType` VARCHAR(100) NULL,
  `attrTarget` VARCHAR(100) NULL,
  `attrId` VARCHAR(100) NULL,
  `attrLang` VARCHAR(45) NULL,
  `value` TEXT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_LEK_term_attributes_1_idx` (`collectionId` ASC),
  INDEX `fk_LEK_term_attributes_2_idx` (`termId` ASC),
  INDEX `fk_LEK_term_attributes_3_idx` (`labelId` ASC),
  CONSTRAINT `fk_LEK_term_attributes_1`
    FOREIGN KEY (`collectionId`)
    REFERENCES `LEK_term_collection` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_LEK_term_attributes_2`
    FOREIGN KEY (`termId`)
    REFERENCES `LEK_terms` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_LEK_term_attributes_3`
    FOREIGN KEY (`labelId`)
    REFERENCES `LEK_term_attributes_label` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE);

CREATE TABLE `LEK_term_entry_attributes` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `labelId` INT NULL,
  `collectionId` INT NULL,
  `termEntryId` INT NULL,
  `parentId` INT NULL DEFAULT NULL,
  `language` VARCHAR(45) NULL DEFAULT NULL,
  `name` VARCHAR(45) NULL,
  `attrType` VARCHAR(100) NULL,
  `attrTarget` VARCHAR(100) NULL,
  `attrId` VARCHAR(100) NULL,
  `attrLang` VARCHAR(45) NULL,
  `value` TEXT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_LEK_term_entry_attributes_1_idx` (`collectionId` ASC),
  INDEX `fk_LEK_term_entry_attributes_2_idx` (`termEntryId` ASC),
  INDEX `fk_LEK_term_entry_attributes_3_idx` (`labelId` ASC),
  CONSTRAINT `fk_LEK_term_entry_attributes_1`
    FOREIGN KEY (`collectionId`)
    REFERENCES `LEK_term_collection` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_LEK_term_entry_attributes_2`
    FOREIGN KEY (`termEntryId`)
    REFERENCES `LEK_term_entry` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_LEK_term_entry_attributes_3`
    FOREIGN KEY (`labelId`)
    REFERENCES `LEK_term_attributes_label` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE);



