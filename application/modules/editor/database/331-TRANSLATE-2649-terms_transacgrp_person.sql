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
CREATE TABLE `terms_transacgrp_person` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `collectionId` int(11) DEFAULT NULL,
   `name` varchar(255) DEFAULT NULL,
   PRIMARY KEY (`id`),
   KEY `ttp_collectionId` (`collectionId`),
   CONSTRAINT `ttp_collectionId` FOREIGN KEY (`collectionId`) REFERENCES `LEK_languageresources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

INSERT INTO `terms_transacgrp_person` (`collectionId`, `name`)
SELECT DISTINCT `collectionId`, `transacNote` FROM `terms_transacgrp` WHERE `termId` IS NOT NULL;

ALTER TABLE `terms_term`   
  CHANGE `updatedBy` `updatedBy` INT(11) NULL COMMENT 'Local instance user (e.g. from Zf_users)',
  ADD COLUMN `tbxCreatedBy` INT(11) NULL COMMENT 'transacgrp: creation responsiblePerson' AFTER `guid`,
  ADD COLUMN `tbxCreatedAt` TIMESTAMP DEFAULT '0000-00-00 00:00:00' NOT NULL AFTER `tbxCreatedBy`,
  ADD COLUMN `tbxUpdatedBy` INT(11) NULL COMMENT 'transacgrp: modification responsiblePerson' AFTER `tbxCreatedAt`,
  ADD COLUMN `tbxUpdatedAt` TIMESTAMP DEFAULT '0000-00-00 00:00:00' NOT NULL AFTER `tbxUpdatedBy`,
  ADD CONSTRAINT `tt_tbxCreatedBy_fk` FOREIGN KEY (`tbxCreatedBy`) REFERENCES `terms_transacgrp_person`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  ADD CONSTRAINT `tt_tbxUpdatedBy_fk` FOREIGN KEY (`tbxUpdatedBy`) REFERENCES `terms_transacgrp_person`(`id`) ON UPDATE CASCADE ON DELETE SET NULL;

UPDATE 
  `terms_term` `tt`,
  `terms_transacgrp` `tr`,
  `terms_transacgrp_person` `trp`
SET
  `tt`.`tbxCreatedBy` = `trp`.`id`,
  `tt`.`tbxCreatedAt` = `tr`.`date`
WHERE 1
  AND `tt`.`id` = `tr`.`termId`
  AND `tt`.`collectionId` = `trp`.`collectionId`
  AND `tr`.`transacType` = 'responsiblePerson'
  AND `tr`.`transac` = 'creation'
  AND `trp`.`name` = `tr`.`transacNote`;

UPDATE 
  `terms_term` `tt`,
  `terms_transacgrp` `tr`,
  `terms_transacgrp_person` `trp`
SET
  `tt`.`tbxUpdatedBy` = `trp`.`id`,
  `tt`.`tbxUpdatedAt` = `tr`.`date`
WHERE 1
  AND `tt`.`id` = `tr`.`termId`
  AND `tt`.`collectionId` = `trp`.`collectionId`
  AND `tr`.`transacType` = 'responsiblePerson'
  AND `tr`.`transac` = 'modification'
  AND `trp`.`name` = `tr`.`transacNote`;
