ALTER TABLE `terms_term` 
  ADD `proposal` TEXT COLLATE utf8mb4_unicode_ci AFTER `term`,
  DROP `tmpOldId`,
  DROP `tmpOldTermEntryId`;

UPDATE `terms_term` `tt`, `terms_proposal` `tp` 
SET `tt`.`proposal` = `tp`.`term`
WHERE `tt`.`id` = `tp`.`termId`;

DROP TABLE `terms_proposal`;

ALTER TABLE `terms_term`   
  CHANGE `updated` `updatedAt` TIMESTAMP DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP NOT NULL;

UPDATE `terms_term` SET `updatedAt` = `created` WHERE `updatedAt` = "0000-00-00 00:00:00";
ALTER TABLE `terms_term` ADD COLUMN `updatedBy` INT(11) NULL AFTER `created`;

UPDATE `terms_term` `t`, `Zf_users` `u`
SET `t`.`updatedBy` = `u`.`id`
WHERE `t`.`userGuid` = `u`.`userGuid`;

ALTER TABLE `terms_term`
  DROP COLUMN `userGuid`, 
  DROP COLUMN `userName`, 
  DROP COLUMN `created`;

DROP TABLE `LEK_term_history`;

ALTER TABLE `translate5`.`terms_term`   
  CHANGE `updatedBy` `updatedBy` INT(11) NULL  AFTER `id`,
  CHANGE `updatedAt` `updatedAt` TIMESTAMP DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP NOT NULL  AFTER `updatedBy`,
  CHANGE `collectionId` `collectionId` INT(11) NOT NULL  AFTER `updatedAt`,
  CHANGE `termEntryId` `termEntryId` INT(11) NULL  AFTER `collectionId`,
  CHANGE `languageId` `languageId` INT(11) NOT NULL  AFTER `termEntryId`,
  CHANGE `language` `language` VARCHAR(36) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci NULL  AFTER `languageId`,
  CHANGE `term` `term` TEXT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL  AFTER `language`,
  CHANGE `proposal` `proposal` TEXT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci NULL  AFTER `term`,
  CHANGE `status` `status` VARCHAR(128) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL  AFTER `proposal`,
  CHANGE `processStatus` `processStatus` VARCHAR(128) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'finalized'  NULL  AFTER `status`,
  CHANGE `definition` `definition` MEDIUMTEXT NULL  AFTER `processStatus`,
  CHANGE `termEntryTbxId` `termEntryTbxId` VARCHAR(100) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci NULL  AFTER `definition`;

CREATE TABLE `terms_term_history` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `termId` INT(11) NOT NULL,
  `collectionId` INT(11) NOT NULL,
  `termEntryId` INT(11) DEFAULT NULL,
  `languageId` INT(11) NOT NULL,
  `language` VARCHAR(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `term` TEXT COLLATE utf8mb4_unicode_ci NOT NULL,
  `proposal` TEXT COLLATE utf8mb4_unicode_ci,
  `status` VARCHAR(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `processStatus` VARCHAR(128) COLLATE utf8mb4_unicode_ci DEFAULT 'finalized',
  `updatedBy` INT(11) DEFAULT NULL,
  `updatedAt` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `definition` MEDIUMTEXT COLLATE utf8mb4_unicode_ci,
  `termEntryTbxId` VARCHAR(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `termTbxId` VARCHAR(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `termEntryGuid` VARCHAR(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `langSetGuid` VARCHAR(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guid` VARCHAR(38) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `terms_attributes` 
  DROP `internalCount`,
  DROP `tmpOldId`,
  DROP `tmpOldTermId`,
  DROP `tmpOldTermEntryId`;

ALTER TABLE `terms_attributes`   
  ADD COLUMN `isCreatedLocally` BOOL DEFAULT 0  NOT NULL AFTER `processStatus`;
UPDATE `terms_attributes` SET `isCreatedLocally` = 1 WHERE `processStatus` != "finalized";
ALTER TABLE `terms_attributes` DROP `processStatus`;

ALTER TABLE `terms_attributes`
  CHANGE `created` `createdAt` TIMESTAMP DEFAULT '0000-00-00 00:00:00' NOT NULL,
  CHANGE `updated` `updatedAt` TIMESTAMP DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP NOT NULL;

ALTER TABLE `terms_attributes`
  ADD COLUMN `createdBy` INT(11) NULL AFTER `userName`,
  ADD COLUMN `updatedBy` INT(11) NULL AFTER `createdAt`;

UPDATE `terms_attributes` `a`, `Zf_users` `u`
SET `a`.`createdBy` = `u`.`id`, `a`.`updatedBy` = `u`.`id`
WHERE `a`.`userGuid` = `u`.`userGuid`;

ALTER TABLE `terms_attributes`
  DROP COLUMN `userGuid`,
  DROP COLUMN `userName`;

DROP TABLE `LEK_term_attribute_history`;

ALTER TABLE `translate5`.`terms_attributes`   
  CHANGE `collectionId` `collectionId` INT(11) NOT NULL  AFTER `id`,
  CHANGE `termEntryId` `termEntryId` INT(11) NULL  AFTER `collectionId`,
  CHANGE `language` `language` VARCHAR(16) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci NULL  AFTER `termEntryId`,
  CHANGE `termId` `termId` INT(11) NULL  AFTER `language`,
  CHANGE `dataTypeId` `dataTypeId` INT(11) NULL  AFTER `termId`,
  CHANGE `type` `type` VARCHAR(100) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci NULL  AFTER `dataTypeId`,
  CHANGE `target` `target` VARCHAR(100) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci NULL  AFTER `value`,
  CHANGE `isCreatedLocally` `isCreatedLocally` TINYINT(1) DEFAULT 0  NOT NULL  AFTER `target`,
  CHANGE `termEntryGuid` `termEntryGuid` VARCHAR(36) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci NULL  AFTER `updatedAt`,
  CHANGE `langSetGuid` `langSetGuid` VARCHAR(36) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci NULL  AFTER `termEntryGuid`,
  CHANGE `termGuid` `termGuid` VARCHAR(36) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci NULL  AFTER `langSetGuid`,
  CHANGE `guid` `guid` VARCHAR(38) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL  AFTER `termGuid`,
  CHANGE `elementName` `elementName` VARCHAR(100) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL  AFTER `guid`,
  CHANGE `attrLang` `attrLang` VARCHAR(36) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci NULL  AFTER `elementName`,
  CHANGE `dataType` `dataType` VARCHAR(100) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci NULL  AFTER `attrLang`;

CREATE TABLE `terms_attributes_history` (
  `id` int(11) NOT NULL DEFAULT '0' AUTO_INCREMENT PRIMARY KEY,
  `attrId` int(11) NOT NULL,
  `collectionId` int(11) NOT NULL,
  `termEntryId` int(11) DEFAULT NULL,
  `language` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `termId` int(11) DEFAULT NULL,
  `dataTypeId` int(11) DEFAULT NULL,
  `type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `target` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `isCreatedLocally` tinyint(1) NOT NULL DEFAULT '0',
  `updatedBy` int(11) DEFAULT NULL,
  `updatedAt` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `termEntryGuid` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `langSetGuid` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `termGuid` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL,
  `elementName` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attrLang` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dataType` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `terms_term_entry`   
  DROP COLUMN `tmpOldId`, 
  CHANGE `isProposal` `isCreatedLocally` TINYINT(1) DEFAULT 0  NULL, 
  DROP INDEX `idx_tmpOldId_te`;