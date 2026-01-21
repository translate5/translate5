-- /*
-- START LICENSE AND COPYRIGHT
--
--  This file is part of translate5
--
--  Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
--
--  Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
--
--  This file may be used under the terms of the GNU GENERAL PUBLIC LICENSE version 3
--  as published by the Free Software Foundation and appearing in the file gpl3-license.txt
--  included in the packaging of this file.  Please review the following information
--  to ensure the GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
--  http://www.gnu.org/licenses/gpl.html
--
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU GENERAL PUBLIC LICENSE version 3
--              http://www.gnu.org/licenses/gpl.html
--
-- END LICENSE AND COPYRIGHT
-- */

ALTER TABLE `match_analysis_pricing_preset`
    ADD COLUMN `isTqeDefault` TINYINT(1) NOT NULL DEFAULT 0;

-- Set the system default preset as also TQE default. One default needs to be selected
UPDATE `match_analysis_pricing_preset` SET `isTqeDefault` = 1 WHERE `isDefault` = 1 AND `customerId` IS NULL LIMIT 1;

ALTER TABLE `LEK_task_meta`
    ADD COLUMN `tqePricingPresetId` INT NULL,
    ADD CONSTRAINT `fk-task_meta-tqe_pricing_preset` FOREIGN KEY (`tqePricingPresetId`)
        REFERENCES `match_analysis_pricing_preset`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `LEK_customer_meta`
    ADD `defaultTqePricingPresetId` INT DEFAULT NULL COMMENT 'Foreign Key to match_analysis_pricing_preset for TQE',
    ADD CONSTRAINT `fk-customer_meta-tqe_pricing_preset` FOREIGN KEY (`defaultTqePricingPresetId`)
        REFERENCES `match_analysis_pricing_preset`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;
