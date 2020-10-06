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
--  translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
--  Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
--  folder of translate5.
--   
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
-- 			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
-- 
-- END LICENSE AND COPYRIGHT
-- */

-- default module because it is used for authentication
INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('default', 'termCustomerSearch', 'frontend', 'ipBasedAuthentication');
INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('default', 'instantTranslate', 'frontend', 'ipBasedAuthentication');

UPDATE `Zf_acl_rules` SET `role`='editor' WHERE `module`='editor' AND `role`='basic' AND `resource`='editor_index' AND `right`='all';

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`,`level`) 
VALUES ('runtimeOptions.authentication.ipbased.IpAddresses', '1', 'editor', 'system', '[]', '[]', '', 'list', 'List of ip addresses for ip based authentication.',4);

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`,`level`) 
VALUES ('runtimeOptions.authentication.ipbased.IpCustomerMap', '1', 'editor', 'system', '{}', '{}', '', 'map', 'Ip address to customer number map. Example where the users coming from 192.168.2.143 are assigned to customer with number 1000 :{"192.168.2.143" : "1000"} . If no ip to customer map is defined, the default customer will be assigned to the ip authenticated users',4);

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`,`level`) 
VALUES ('runtimeOptions.authentication.ipbased.userRoles', '1', 'editor', 'system', '["instantTranslate","termCustomerSearch"]', '["instantTranslate","termCustomerSearch"]', '', 'list', 'User roles for ip base authenticated user.',4);

-- add the plugin into an existing plugin config
UPDATE  `Zf_configuration`
SET  `value` = REPLACE(`value`, '"]', '","editor_Plugins_IpAuthentication_Init"]')
WHERE  `Zf_configuration`.`name` ="runtimeOptions.plugins.active" and not `Zf_configuration`.`value` like '%editor_Plugins_IpAuthentication_Init%';