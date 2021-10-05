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

INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('editor', 'pm', 'frontend', 'pluginOkapiBconfPrefs');
INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('editor', 'pm', 'editor_plugins_okapi_bconf', 'all');
INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('editor', 'pm', 'editor_plugins_okapi_bconffilter', 'all');

CREATE TABLE `LEK_okapi_bconf` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50),
    `customer_id` VARCHAR(50),
    `default` BOOLEAN,
    `extensions` VARCHAR(200),
    `description` TEXT,
    PRIMARY KEY (`id`)
);

CREATE TABLE `LEK_okapi_bconf_filter` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`okapiId` int(11) NOT NULL,
	`configId` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
	`okapiName` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
	`mime` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
	`default` bit(1) DEFAULT NULL,
	`name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
	`notes` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
	`extensions` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
	`configuration` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
	`codeId` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
	PRIMARY KEY (`id`),
	CONSTRAINT `fk_LEK_okapi_bconf_filter` FOREIGN KEY (`okapiId`) REFERENCES `LEK_okapi_bconf` (`id`) ON DELETE CASCADE
);

CREATE TABLE `LEK_okapi_bconf_default_filter` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `configId` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mimeType` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `extensions` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    PRIMARY KEY (`id`)
)
