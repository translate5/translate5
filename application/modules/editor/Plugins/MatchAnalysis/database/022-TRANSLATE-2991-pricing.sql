-- /*
-- START LICENSE AND COPYRIGHT
--
--  This file is part of translate5
--
--  Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
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
--              http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
--
-- END LICENSE AND COPYRIGHT
-- */

-- Define presets table
CREATE TABLE IF NOT EXISTS `match_analysis_pricing_preset` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `customerId` INT DEFAULT NULL,
    `name` VARCHAR(50),
    `unitType` ENUM('word', 'character') NOT NULL DEFAULT 'word',
    `description` TEXT,
    `priceAdjustment` INT NOT NULL DEFAULT 0,
    `isDefault` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE(`name`),
    CONSTRAINT FOREIGN KEY (`customerId`) REFERENCES `LEK_customer` (`id`) ON DELETE CASCADE
);

-- Insert system default preset
INSERT INTO `match_analysis_pricing_preset` VALUES (1, NULL, 'Translate5-Standard', 'word', '', 0, 1);

-- Define ranges table
CREATE TABLE IF NOT EXISTS `match_analysis_pricing_preset_range` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `presetId` INT NOT NULL,
    `from` INT NOT NULL,
    `till` INT NOT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT FOREIGN KEY (`presetId`) REFERENCES `match_analysis_pricing_preset` (`id`) ON DELETE CASCADE
);

-- Insert ranges for system default preset
INSERT INTO `match_analysis_pricing_preset_range` (`presetId`, `from`, `till`) VALUES
(1,  50,  59),
(1,  60,  69),
(1,  70,  79),
(1,  80,  89),
(1,  90,  99),
(1, 100, 100),
(1, 101, 101),
(1, 102, 102),
(1, 103, 103),
(1, 104, 104);

-- Define values (prices) table
CREATE TABLE IF NOT EXISTS `match_analysis_pricing_preset_prices` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `presetId` INT NOT NULL,
    `sourceLanguageId` INT NOT NULL,
    `targetLanguageId` INT NOT NULL,
    `currency` VARCHAR(3) NOT NULL DEFAULT '',
    `pricesByRangeIds` JSON,
    PRIMARY KEY (`id`),
    CONSTRAINT FOREIGN KEY (`presetId`) REFERENCES `match_analysis_pricing_preset` (`id`) ON DELETE CASCADE,
    CONSTRAINT FOREIGN KEY (`sourceLanguageId`) REFERENCES `LEK_languages` (`id`) ON DELETE RESTRICT,
    CONSTRAINT FOREIGN KEY (`targetLanguageId`) REFERENCES `LEK_languages` (`id`) ON DELETE RESTRICT
);

-- Add constraints at last, so tables are created even when constraints fail
ALTER TABLE `LEK_customer_meta`
    ADD `defaultPricingPresetId` INT DEFAULT NULL COMMENT 'Foreign Key to match_analysis_pricing_preset',
    ADD CONSTRAINT `fk-customer_meta-match_analysis_pricing_preset` FOREIGN KEY (`defaultPricingPresetId`)
        REFERENCES `match_analysis_pricing_preset`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `LEK_task_meta`
    ADD COLUMN `pricingPresetId` INT NULL,
    ADD CONSTRAINT `fk-task_meta-match_analysis_pricing_preset` FOREIGN KEY (`pricingPresetId`)
        REFERENCES `match_analysis_pricing_preset`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Add runtimeOptions.plugins.MatchAnalysis.pricing.defaultCurrency config
INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`,`value`,  `default`, `defaults`, `type`,
                                `typeClass`,`description`,`level`,`guiName`,`guiGroup`,`comment`)
VALUES ('runtimeOptions.plugins.MatchAnalysis.pricing.defaultCurrency',1,'editor','plugins','€','€','','string',NULL,
        'Define the default currency in which the prices for all matchrate ranges are defined for the certain target language',
        2,'Analysis pricing default currency','Match analysis: defaults',NULL);

-- Add acl-records
INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`)
VALUES ('editor', 'pm', 'frontend', 'pluginMatchAnalysisPricingPreset');

INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`)
VALUES ('editor', 'editor', 'editor_plugins_matchanalysis_pricingpreset', 'index');

INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES
('editor', 'pm', 'editor_plugins_matchanalysis_pricingpreset'      , 'all'),
('editor', 'pm', 'editor_plugins_matchanalysis_pricingpresetprices', 'all'),
('editor', 'pm', 'editor_plugins_matchanalysis_pricingpresetrange' , 'all');