-- /*
-- START LICENSE AND COPYRIGHT
--
--  This file is part of translate5
--
--  Copyright (c) 2013 - 2025 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
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

-- Create separate table for file translation preselection defaults (TRANSLATE-5268)
CREATE TABLE IF NOT EXISTS `LEK_user_preselection` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `userId` INT NOT NULL,
    `sourceLangFileDefault` INT NULL COMMENT 'Default source language for file translation',
    `targetLangFileDefault` INT NULL COMMENT 'Default target language for file translation (single-language mode)',
    `targetLangFileDefaultMulti` TEXT NULL COMMENT 'Default target selections for file translation in multi-language mode (JSON array of resourceId|langCode values)',
    `fileCustomerDefault` INT NULL COMMENT 'Default customer for file translation',
    PRIMARY KEY (`id`),
    UNIQUE INDEX `uk_user_preselection_userId` (`userId` ASC),
    INDEX `fk_user_preselection_source_lang_idx` (`sourceLangFileDefault` ASC),
    INDEX `fk_user_preselection_target_lang_idx` (`targetLangFileDefault` ASC),
    INDEX `fk_user_preselection_customer_idx` (`fileCustomerDefault` ASC),
    CONSTRAINT `fk_user_preselection_user`
        FOREIGN KEY (`userId`)
        REFERENCES `Zf_users` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `fk_user_preselection_source_lang`
        FOREIGN KEY (`sourceLangFileDefault`)
        REFERENCES `LEK_languages` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT `fk_user_preselection_target_lang`
        FOREIGN KEY (`targetLangFileDefault`)
        REFERENCES `LEK_languages` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT `fk_user_preselection_customer`
        FOREIGN KEY (`fileCustomerDefault`)
        REFERENCES `LEK_customer` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrate existing data from LEK_user_meta if columns exist
INSERT IGNORE INTO `LEK_user_preselection` (`userId`, `sourceLangFileDefault`, `targetLangFileDefault`, `targetLangFileDefaultMulti`, `fileCustomerDefault`)
SELECT `userId`, `sourceLangFileDefault`, `targetLangFileDefault`, `targetLangFileDefaultMulti`, `fileCustomerDefault`
FROM `LEK_user_meta`
WHERE `sourceLangFileDefault` IS NOT NULL
   OR `targetLangFileDefault` IS NOT NULL
   OR `targetLangFileDefaultMulti` IS NOT NULL
   OR `fileCustomerDefault` IS NOT NULL;

-- Drop the migrated columns from LEK_user_meta
ALTER TABLE `LEK_user_meta`
DROP FOREIGN KEY `fk_LEK_user_meta_source_file`,
DROP FOREIGN KEY `fk_LEK_user_meta_target_file`,
DROP FOREIGN KEY `fk_LEK_user_meta_customer`,
DROP INDEX `fk_LEK_user_meta_source_file_idx`,
DROP INDEX `fk_LEK_user_meta_target_file_idx`,
DROP INDEX `fk_LEK_user_meta_customer_idx`,
DROP COLUMN `sourceLangFileDefault`,
DROP COLUMN `targetLangFileDefault`,
DROP COLUMN `targetLangFileDefaultMulti`,
DROP COLUMN `fileCustomerDefault`;
