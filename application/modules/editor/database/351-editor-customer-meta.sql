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

CREATE TABLE `LEK_customer_meta` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `customerId` int(11) NOT NULL COMMENT 'Foreign Key to LEK_customer',
  `defaultBconfId` int(11) DEFAULT NULL COMMENT 'Foreign Key to LEK_okapi_bconf',
  PRIMARY KEY (`id`),
  UNIQUE KEY `customerId` (`customerId`),
  KEY `defaultBconfId` (`defaultBconfId`),
  CONSTRAINT `fk-customer_meta-customer` FOREIGN KEY (`customerId`) REFERENCES `LEK_customer` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk-customer_meta-okapi_bconf` FOREIGN KEY (`defaultBconfId`) REFERENCES `LEK_okapi_bconf` (`id`) ON DELETE CASCADE
)
