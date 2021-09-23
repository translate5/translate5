-- /*
-- START LICENSE AND COPYRIGHT
--
--  This file is part of translate5
--
--  Copyright (c) 2013 - '.(date('Y')).' Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
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

DROP TABLE IF EXISTS `terms_attributes_history`;
DROP TABLE IF EXISTS `terms_attributes_proposal`;
DROP TABLE IF EXISTS `terms_attributes`;
DROP TABLE IF EXISTS `terms_collection_attribute_datatype`;
DROP TABLE IF EXISTS `terms_attributes_datatype`;
DROP TABLE IF EXISTS `terms_images`;
DROP TABLE IF EXISTS `terms_ref_object`;
DROP TABLE IF EXISTS `terms_term_history`;
DROP TABLE IF EXISTS `terms_transacgrp`;
DROP TABLE IF EXISTS `terms_term`;
DROP TABLE IF EXISTS `terms_term_entry`;

CREATE TABLE `terms_term_entry` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `collectionId` int(11) DEFAULT NULL,
  `termEntryTbxId` varchar(100) NOT NULL,
  `isCreatedLocally` tinyint(1) DEFAULT 0,
  `entryGuid` varchar(38) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `terms_term_entry_entryGuid_uindex` (`entryGuid`),
  KEY `collectionId_idx` (`collectionId`),
  KEY `termEntryTbxId_idx` (`termEntryTbxId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `terms_term` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `updatedBy` int(11) DEFAULT NULL,
  `updatedAt` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  `collectionId` int(11) NOT NULL,
  `termEntryId` int(11) DEFAULT NULL,
  `languageId` int(11) NOT NULL,
  `language` varchar(36) DEFAULT NULL,
  `term` varchar(255) NOT NULL,
  `proposal` varchar(255) DEFAULT NULL,
  `status` varchar(128) NOT NULL,
  `processStatus` varchar(128) DEFAULT 'finalized',
  `definition` mediumtext DEFAULT NULL,
  `termEntryTbxId` varchar(100) DEFAULT NULL,
  `termTbxId` varchar(100) DEFAULT NULL,
  `termEntryGuid` varchar(36) DEFAULT NULL,
  `langSetGuid` varchar(36) DEFAULT NULL,
  `guid` varchar(38) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `terms_term_guid_uindex` (`guid`),
  KEY `collectionId_idx` (`collectionId`),
  KEY `termEntryId_idx` (`termEntryId`),
  KEY `termTbxId_idx` (`termTbxId`),
  KEY `languageId_collectionId_idx` (`collectionId`,`languageId`),
  KEY `termEntryTbxId_idx` (`termEntryTbxId`),
  FULLTEXT KEY `fulltext` (`term`,`proposal`),
  FULLTEXT KEY `fulltext_term` (`term`),
  CONSTRAINT `terms_term_collection_ibfk_1` FOREIGN KEY (`collectionId`) REFERENCES `LEK_languageresources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `terms_term_entry_ibfk_1` FOREIGN KEY (`termEntryId`) REFERENCES `terms_term_entry` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `terms_termentry_ibfk_1` FOREIGN KEY (`termEntryId`) REFERENCES `terms_term_entry` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `terms_term_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `termId` int(11) NOT NULL,
  `collectionId` int(11) NOT NULL,
  `termEntryId` int(11) DEFAULT NULL,
  `languageId` int(11) NOT NULL,
  `language` varchar(36) DEFAULT NULL,
  `term` text NOT NULL,
  `proposal` text DEFAULT NULL,
  `status` varchar(128) NOT NULL,
  `processStatus` varchar(128) DEFAULT 'finalized',
  `updatedBy` int(11) DEFAULT NULL,
  `updatedAt` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  `definition` mediumtext DEFAULT NULL,
  `termEntryTbxId` varchar(100) DEFAULT NULL,
  `termTbxId` varchar(100) DEFAULT NULL,
  `termEntryGuid` varchar(36) DEFAULT NULL,
  `langSetGuid` varchar(36) DEFAULT NULL,
  `guid` varchar(38) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `collectionId` (`collectionId`),
  KEY `termEntryId` (`termEntryId`),
  KEY `termId` (`termId`),
  CONSTRAINT `collectionId` FOREIGN KEY (`collectionId`) REFERENCES `LEK_languageresources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `termEntryId` FOREIGN KEY (`termEntryId`) REFERENCES `terms_term_entry` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `termId` FOREIGN KEY (`termId`) REFERENCES `terms_term` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `terms_transacgrp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `elementName` varchar(20) DEFAULT NULL,
  `transac` text DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `transacNote` text DEFAULT NULL,
  `transacType` varchar(255) DEFAULT NULL,
  `target` varchar(64) DEFAULT NULL,
  `language` varchar(12) DEFAULT NULL,
  `isDescripGrp` tinyint(1) DEFAULT 0,
  `collectionId` int(11) NOT NULL,
  `termEntryId` int(11) DEFAULT NULL,
  `termId` int(11) DEFAULT NULL,
  `termTbxId` varchar(100) DEFAULT NULL,
  `termGuid` varchar(36) DEFAULT NULL,
  `termEntryGuid` varchar(36) DEFAULT NULL,
  `langSetGuid` varchar(36) DEFAULT NULL,
  `guid` varchar(38) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `terms_transacgrp_guid_uindex` (`guid`),
  KEY `collectionId_idx` (`collectionId`),
  KEY `termEntryId_idx` (`termEntryId`),
  KEY `terms_tgrp_term_ibfk_1` (`termId`),
  KEY `termTbxId` (`termTbxId`),
  CONSTRAINT `terms_tgrp_collection_ibfk_1` FOREIGN KEY (`collectionId`) REFERENCES `LEK_languageresources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `terms_tgrp_entry_ibfk_1` FOREIGN KEY (`termEntryId`) REFERENCES `terms_term_entry` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `terms_tgrp_term_ibfk_1` FOREIGN KEY (`termId`) REFERENCES `terms_term` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `terms_attributes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `collectionId` int(11) NOT NULL,
  `termEntryId` int(11) DEFAULT NULL,
  `language` varchar(16) DEFAULT NULL,
  `termId` int(11) DEFAULT NULL,
  `termTbxId` varchar(100) DEFAULT NULL,
  `dataTypeId` int(11) DEFAULT NULL,
  `type` varchar(100) DEFAULT NULL,
  `value` text DEFAULT NULL,
  `target` varchar(100) DEFAULT NULL,
  `isCreatedLocally` tinyint(1) NOT NULL DEFAULT 0,
  `createdBy` int(11) DEFAULT NULL,
  `createdAt` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updatedBy` int(11) DEFAULT NULL,
  `updatedAt` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  `termEntryGuid` varchar(36) DEFAULT NULL,
  `langSetGuid` varchar(36) DEFAULT NULL,
  `termGuid` varchar(36) DEFAULT NULL,
  `guid` varchar(38) NOT NULL,
  `elementName` varchar(100) NOT NULL,
  `attrLang` varchar(36) DEFAULT NULL,
  `isDescripGrp` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `terms_attributes_guid_uindex` (`guid`),
  KEY `collectionId_idx` (`collectionId`),
  KEY `termId_idx` (`termId`),
  KEY `termEntryId_idx` (`termEntryId`),
  KEY `termTbxId_idx` (`termTbxId`),
  KEY `dataTypeId_idx` (`dataTypeId`),
  CONSTRAINT `terms_collection_ibfk_1` FOREIGN KEY (`collectionId`) REFERENCES `LEK_languageresources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `terms_entry_ibfk_1` FOREIGN KEY (`termEntryId`) REFERENCES `terms_term_entry` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `terms_term_ibfk_1` FOREIGN KEY (`termId`) REFERENCES `terms_term` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `terms_attributes_datatype` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(100) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `l10nSystem` varchar(255) DEFAULT NULL,
  `l10nCustom` varchar(255) DEFAULT NULL,
  `level` set('entry','language','term') DEFAULT 'entry,language,term' COMMENT 'Level represented as comma separated values where the label(attribute) can appear. entry,language,term',
  `dataType` enum('plainText','noteText','basicText','picklist','Language code','date') DEFAULT 'plainText',
  `picklistValues` varchar(255) DEFAULT NULL COMMENT 'Available comma separated values for selecting for the attribute when the attributa dataType is picklist.',
  `isTbxBasic` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `terms_attributes_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `attrId` int(11) NOT NULL,
  `collectionId` int(11) NOT NULL,
  `termEntryId` int(11) DEFAULT NULL,
  `language` varchar(16) DEFAULT NULL,
  `termId` int(11) DEFAULT NULL,
  `dataTypeId` int(11) DEFAULT NULL,
  `type` varchar(100) DEFAULT NULL,
  `value` text DEFAULT NULL,
  `target` varchar(100) DEFAULT NULL,
  `isCreatedLocally` tinyint(1) NOT NULL DEFAULT 0,
  `updatedBy` int(11) DEFAULT NULL,
  `updatedAt` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  `termEntryGuid` varchar(36) DEFAULT NULL,
  `langSetGuid` varchar(36) DEFAULT NULL,
  `termGuid` varchar(36) DEFAULT NULL,
  `guid` varchar(38) NOT NULL,
  `elementName` varchar(100) NOT NULL,
  `attrLang` varchar(36) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tah_collectionId` (`collectionId`),
  KEY `tah_termEntryId` (`termEntryId`),
  KEY `tah_termId` (`termId`),
  KEY `tah_attrId` (`attrId`),
  CONSTRAINT `tah_attrId` FOREIGN KEY (`attrId`) REFERENCES `terms_attributes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `tah_collectionId` FOREIGN KEY (`collectionId`) REFERENCES `LEK_languageresources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `tah_termEntryId` FOREIGN KEY (`termEntryId`) REFERENCES `terms_term_entry` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `tah_termId` FOREIGN KEY (`termId`) REFERENCES `terms_term` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `terms_collection_attribute_datatype` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `collectionId` int(11) DEFAULT NULL,
  `dataTypeId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `indexCollectionIdAndDataTypeId` (`collectionId`,`dataTypeId`),
  KEY `fk_terms_collection_attribute_datatype_1_idx` (`collectionId`),
  KEY `fk_terms_collection_attribute_datatype_2_idx` (`dataTypeId`),
  CONSTRAINT `fk_terms_collection_attribute_datatype_1` FOREIGN KEY (`collectionId`) REFERENCES `LEK_languageresources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_terms_collection_attribute_datatype_2` FOREIGN KEY (`dataTypeId`) REFERENCES `terms_attributes_datatype` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `terms_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `targetId` varchar(255) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `uniqueName` varchar(100) DEFAULT NULL,
  `format` varchar(255) DEFAULT NULL,
  `collectionId` int(11) NOT NULL,
  `contentMd5hash` varchar(32) DEFAULT NULL COMMENT 'md5 hash of the file content, mainly to check if a file update on merge import is needed',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniqueName_UNIQUE` (`uniqueName`),
  KEY `fk_terms_images_languageresources` (`collectionId`),
  CONSTRAINT `fk_terms_images_languageresources` FOREIGN KEY (`collectionId`) REFERENCES `LEK_languageresources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `terms_ref_object` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `collectionId` int(11) NOT NULL,
  `listType` varchar(64) NOT NULL,
  `key` varchar(64) NOT NULL,
  `data` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `collectionId` (`collectionId`,`listType`,`key`),
  CONSTRAINT `terms_ref_object_ibfk_1` FOREIGN KEY (`collectionId`) REFERENCES `LEK_languageresources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- preparations on current database structure
ALTER TABLE LEK_terms ADD COLUMN userId int(11) DEFAULT NULL;
ALTER TABLE LEK_term_proposal ADD COLUMN userId int(11) DEFAULT NULL;
ALTER TABLE LEK_term_history ADD COLUMN userId int(11) DEFAULT NULL;
ALTER TABLE LEK_term_attribute_history ADD COLUMN userId int(11) DEFAULT NULL;

-- create dedicated migration table
CREATE TABLE `terms_migration_langset` (
   `collectionId` int(11) NOT NULL,
   `termEntryId` int(11) DEFAULT NULL,
   `languageId` int(11) NOT NULL,
   `language` varchar(36) DEFAULT NULL,
   `langSetGuid` varchar(36) DEFAULT NULL,
   PRIMARY KEY (`langSetGuid`),
   KEY `collectionId_idx` (`collectionId`),
   UNIQUE `languageId_collectionId_idx` (`collectionId`,`termEntryId`,`languageId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;