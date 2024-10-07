-- START LICENSE AND COPYRIGHT
--
--  This file is part of translate5
--
--  Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
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

CREATE TABLE `LEK_language_service_provider` (
    `id` int (11) NOT NULL AUTO_INCREMENT,
    `parentId` int (11) DEFAULT NULL COMMENT 'Foreign Key to LEK_language_service_provider',
    `name` varchar(255) NOT NULL,
    `description` varchar(255) DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT FOREIGN KEY (`parentId`) REFERENCES `LEK_language_service_provider` (`id`) ON DELETE RESTRICT
);

CREATE TABLE `LEK_language_service_provider_customer` (
    `id` int (11) NOT NULL AUTO_INCREMENT,
    `lspId` int (11) DEFAULT NULL COMMENT 'Foreign Key to LEK_language_service_provider',
    `customerId` int (11) NOT NULL COMMENT 'Foreign Key to LEK_customer',
    PRIMARY KEY (`id`),
    CONSTRAINT FOREIGN KEY (`lspId`) REFERENCES `LEK_language_service_provider` (`id`) ON DELETE CASCADE,
    CONSTRAINT FOREIGN KEY (`customerId`) REFERENCES `LEK_customer` (`id`) ON DELETE CASCADE
);

CREATE TABLE `LEK_language_service_provider_user` (
    `id` int (11) NOT NULL AUTO_INCREMENT,
    `guid` varchar(38) NOT NULL,
    `lspId` int (11) DEFAULT NULL COMMENT 'Foreign Key to LEK_language_service_provider',
    `userId` int (11) NOT NULL COMMENT 'Foreign Key to Zf_users',
    PRIMARY KEY (`id`),
    CONSTRAINT FOREIGN KEY (`lspId`) REFERENCES `LEK_language_service_provider` (`id`) ON DELETE RESTRICT,
    CONSTRAINT FOREIGN KEY (`userId`) REFERENCES `Zf_users` (`id`) ON DELETE CASCADE,
    UNIQUE (`userId`)
);

ALTER TABLE `LEK_taskUserAssoc` ADD `type` TINYINT DEFAULT 1 NOT NULL;

