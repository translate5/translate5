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

-- add user data fields
ALTER TABLE `LEK_term_proposal` 
ADD COLUMN `userGuid` varchar(38) NOT NULL,
ADD COLUMN `userName` varchar(255) NOT NULL DEFAULT '',
ADD UNIQUE KEY (`termId`);

ALTER TABLE `LEK_terms` 
ADD COLUMN `userGuid` varchar(38) NOT NULL,
ADD COLUMN `userName` varchar(255) NOT NULL DEFAULT '';

ALTER TABLE `LEK_term_attributes`
ADD COLUMN `userGuid` varchar(38) NOT NULL,
ADD COLUMN `userName` varchar(255) NOT NULL DEFAULT '',
ADD COLUMN `termEntryId` int(11) DEFAULT NULL AFTER termId,
ADD COLUMN `oldId` int(11) DEFAULT NULL AFTER termEntryId,
ADD COLUMN `processStatus` varchar(128) DEFAULT 'finalized' COMMENT "old term processStatus",
ADD CONSTRAINT FOREIGN KEY (`termEntryId`) REFERENCES `LEK_term_entry` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- copy term entry attributes into term attributes
INSERT INTO `LEK_term_attributes` (`id`, `oldId`, `labelId`, `collectionId`, `termId`, `termEntryId`, `parentId`,  `internalCount`, `language`, `name`, `attrType`, `attrDataType`, `attrTarget`, `attrId`, `attrLang`, `value`, `created`, `updated`)
SELECT null as `id`, `id` as `oldId`, `labelId`, `collectionId`, null as `termId`, `termEntryId`, `parentId`,  `internalCount`, `language`, `name`, `attrType`, `attrDataType`, `attrTarget`, `attrId`, `attrLang`, `value`, `created`, `updated` FROM `LEK_term_entry_attributes`;

-- update the parentId values of the copied term entry attributes to the new ids of the entries
UPDATE `LEK_term_attributes` attr_target, `LEK_term_attributes` attr_source
SET attr_target.`parentId` = attr_source.`id`
WHERE 
  NOT attr_target.`parentId` IS NULL 
  AND attr_target.`parentId` = attr_source.`oldId`
  AND attr_target.`termId` IS NULL 
  AND attr_source.`termId` IS NULL;

-- remove helper field
ALTER TABLE `LEK_term_attributes`
DROP COLUMN `oldId`;

-- remove old entry table
DROP TABLE `LEK_term_entry_attributes`;

CREATE TABLE `LEK_term_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `historyCreated` timestamp NOT NULL DEFAULT current_timestamp() COMMENT "timestamp of history entry creation",
  `termId` int(11) NOT NULL COMMENT "reference to the term",
  `collectionId` int(11) NOT NULL COMMENT "reference to the collection",
  `term` varchar(19000) NOT NULL DEFAULT '' COMMENT "old term value",
  `status` varchar(255) NOT NULL COMMENT "old term status",
  `processStatus` varchar(128) DEFAULT 'finalized' COMMENT "old term processStatus",
  `definition` text DEFAULT NULL COMMENT "old term definition",
  `userGuid` varchar(38) NOT NULL COMMENT "editing user of old term version",
  `userName` varchar(255) NOT NULL DEFAULT '' COMMENT "editing user of old term version",
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT "creation date of old term version",
  `updated` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT "editing date of old term version",
  PRIMARY KEY (`id`),
  CONSTRAINT FOREIGN KEY (`termId`) REFERENCES `LEK_terms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT FOREIGN KEY (`collectionId`) REFERENCES `LEK_languageresources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `LEK_term_attribute_proposal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `value` text NOT NULL DEFAULT '' COMMENT 'the proposed value',
  `collectionId` int(11) NOT NULL COMMENT 'links to the collection',
  `attributeId` int(11) DEFAULT NULL COMMENT 'links to the attribute',
  `userGuid` varchar(38) NOT NULL,
  `userName` varchar(255) NOT NULL DEFAULT '',
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  CONSTRAINT FOREIGN KEY (`attributeId`) REFERENCES `LEK_term_attributes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT FOREIGN KEY (`collectionId`) REFERENCES `LEK_languageresources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `LEK_term_attribute_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `historyCreated` timestamp NOT NULL DEFAULT current_timestamp() COMMENT "timestamp of history entry creation",
  `attributeId` int(11) NOT NULL COMMENT "reference to the attribute",
  `collectionId` int(11) NOT NULL COMMENT "reference to the collection",
  `value` text NOT NULL DEFAULT '' COMMENT "old attribute value",
  `processStatus` varchar(128) DEFAULT 'finalized' COMMENT "old attribute processStatus",
  `userGuid` varchar(38) NOT NULL COMMENT "editing user of old attribute version",
  `userName` varchar(255) NOT NULL DEFAULT '' COMMENT "editing user of old attribute version",
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT "creation date of old attribute version",
  `updated` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT "editing date of old attribute version",
  PRIMARY KEY (`id`),
  CONSTRAINT FOREIGN KEY (`attributeId`) REFERENCES `LEK_term_attributes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT FOREIGN KEY (`collectionId`) REFERENCES `LEK_languageresources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;
