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

DELIMITER ;;
CREATE PROCEDURE FIX_ALTER_TRANSACGRP()
BEGIN
    DECLARE EXIT HANDLER FOR 1060 BEGIN END;

    ALTER TABLE `terms_transacgrp_person`
        ADD COLUMN `collectionId` INT(11) NULL AFTER `id`,
        ADD CONSTRAINT `ttp_collectionId` FOREIGN KEY (`collectionId`) REFERENCES `LEK_languageresources`(`id`) ON UPDATE CASCADE ON DELETE CASCADE;

    DELETE FROM `terms_transacgrp_person`;

    INSERT INTO `terms_transacgrp_person` (`collectionId`, `name`)
    SELECT DISTINCT `collectionId`, `transacNote` FROM `terms_transacgrp` WHERE `termId` IS NOT NULL;

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
    AND `tr`.`transacType` = 'responsibility'
    AND `tr`.`transac` = 'origination'
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
    AND `tr`.`transacType` = 'responsibility'
    AND `tr`.`transac` = 'modification'
    AND `trp`.`name` = `tr`.`transacNote`;
    
END;;
DELIMITER ;
CALL FIX_ALTER_TRANSACGRP();
DROP PROCEDURE FIX_ALTER_TRANSACGRP;
