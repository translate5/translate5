-- /*
-- START LICENSE AND COPYRIGHT
-- 
--  This file is part of translate5
--  
--  Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
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
CREATE TABLE `LEK_customer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `number` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `number_UNIQUE` (`number`),
  KEY `name` (`name`),
  KEY `number` (`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) 
  VALUES ('editor', 'pm', 'frontend', 'customerAdministration');

INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) 
  VALUES ('editor', 'pm', 'backend', 'customerAdministration');

INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`)
  VALUES ('editor', 'pm', 'frontend', 'pluginCustomerCustomer');

INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) 
  VALUES ('editor', 'pm', 'editor_plugins_customer_customer', 'all');

ALTER TABLE `Zf_users` 
  ADD COLUMN `customers` VARCHAR(255) NULL;

