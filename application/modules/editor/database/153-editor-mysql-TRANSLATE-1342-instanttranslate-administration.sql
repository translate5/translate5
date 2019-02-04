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

CREATE TABLE `LEK_languageresources_customerassoc` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `languageResourceId` int(11) DEFAULT NULL,
  `customerId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_LEK_languageresources_customerassoc_1_idx` (`languageResourceId`),
  KEY `fk_LEK_languageresources_customerassoc_2_idx` (`customerId`),
  CONSTRAINT `fk_LEK_languageresources_customerassoc_1` FOREIGN KEY (`languageResourceId`) REFERENCES `LEK_languageresources` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_LEK_languageresources_customerassoc_2` FOREIGN KEY (`customerId`) REFERENCES `LEK_customer` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;


ALTER TABLE `LEK_languageresources` 
ADD COLUMN `defaultCustomer` INT(11) NULL AFTER `fileName`;


ALTER TABLE `LEK_languageresources` 
CHANGE COLUMN `fileName` `specificData` VARCHAR(1024) NULL DEFAULT NULL COMMENT 'Language resource specific info data' ;

INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('editor', 'pm', 'editor_instanttranslateapi', 'all');

CREATE TABLE `LEK_user_meta` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `userId` INT NULL,
  `sourceLangDefault` INT NULL,
  `targetLangDefault` INT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_LEK_user_meta_1_idx` (`userId` ASC),
  INDEX `fk_LEK_user_meta_2_idx` (`sourceLangDefault` ASC),
  INDEX `fk_LEK_user_meta_3_idx` (`targetLangDefault` ASC),
  CONSTRAINT `fk_LEK_user_meta_1`
    FOREIGN KEY (`userId`)
    REFERENCES `Zf_users` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_LEK_user_meta_2`
    FOREIGN KEY (`sourceLangDefault`)
    REFERENCES `LEK_languages` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_LEK_user_meta_3`
    FOREIGN KEY (`targetLangDefault`)
    REFERENCES `LEK_languages` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION);


CREATE TABLE `LEK_languageresources_languages` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `sourceLang` INT NULL,
  `targetLang` INT NULL,
  `sourceLangRfc5646` VARCHAR(45) NULL,
  `targetLangRfc5646` VARCHAR(45) NULL,
  `languageResourceId` INT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_LEK_languageresources_languages_1_idx` (`languageResourceId` ASC),
  CONSTRAINT `fk_LEK_languageresources_languages_1`
    FOREIGN KEY (`languageResourceId`)
    REFERENCES `LEK_languageresources` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE);
    
/* move the existing languages into the new table */
INSERT INTO LEK_languageresources_languages (sourceLang,targetLang,sourceLangRfc5646,targetLangRfc5646,languageResourceId) 
SELECT  sourceLang, 
        targetLang, 
        sourceLangRfc5646, 
        targetLangRfc5646,
        id
FROM LEK_languageresources;

ALTER TABLE `LEK_languageresources` 
DROP COLUMN `targetLangRfc5646`,
DROP COLUMN `targetLang`,
DROP COLUMN `sourceLangRfc5646`,
DROP COLUMN `sourceLang`;

