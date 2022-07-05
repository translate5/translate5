--  START LICENSE AND COPYRIGHT
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
--  		     http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
--
--  END LICENSE AND COPYRIGHT

ALTER TABLE `LEK_okapi_bconf_filter` ADD `okapiType` VARCHAR(50) NOT NULL AFTER `bconfId`;
ALTER TABLE `LEK_okapi_bconf_filter` MODIFY `okapiId` VARCHAR(255) NOT NULL;
ALTER TABLE `LEK_okapi_bconf_filter` ADD `description` VARCHAR(255) NOT NULL default '' AFTER `notes`;
ALTER TABLE `LEK_okapi_bconf_filter` MODIFY `mimeType` varchar(50) NOT NULL default '';
ALTER TABLE `LEK_okapi_bconf_filter` MODIFY `name` varchar(100) NOT NULL;
ALTER TABLE `LEK_okapi_bconf_filter` MODIFY `extensions` varchar(255) NOT NULL;
ALTER TABLE `LEK_okapi_bconf_filter` ADD `hash` VARCHAR(32) NOT NULL;
ALTER TABLE `LEK_okapi_bconf_filter` DROP COLUMN `okapiName`;
ALTER TABLE `LEK_okapi_bconf_filter` DROP COLUMN `notes`;

DROP TABLE IF EXISTS `LEK_okapi_bconf_default_filter`;