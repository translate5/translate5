-- /*
-- START LICENSE AND COPYRIGHT
--
--  This file is part of translate5
--
--  Copyright (c) 2013 - 2020 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
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

CREATE TABLE `LEK_task_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `taskGuidConfigName` (`taskGuid`,`name`),
  KEY `taskGuidIdx` (`taskGuid`),
  KEY `configNameIdx` (`name`),
  CONSTRAINT `LEK_task_fk` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE,
  CONSTRAINT `Zf_configuration_fk` FOREIGN KEY (`name`) REFERENCES `Zf_configuration` (`name`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `LEK_customer_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customerId` int(11) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customerIdConfigName` (`customerId`,`name`),
  KEY `customerIdIdx` (`customerId`),
  KEY `configNameIdx` (`name`),
  CONSTRAINT `LEK_customer-LEK_customer_config-fk` FOREIGN KEY (`customerId`) REFERENCES `LEK_customer` (`id`) ON DELETE CASCADE,
  CONSTRAINT `Zf_configuration-LEK_customer_config-fk` FOREIGN KEY (`name`) REFERENCES `Zf_configuration` (`name`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

UPDATE `Zf_configuration` SET `level`='8' WHERE 
`name`='runtimeOptions.segments.stateFlags';

UPDATE `Zf_configuration` SET `level`='4' WHERE 
 `name`='runtimeOptions.customers.anonymizeUsers';
