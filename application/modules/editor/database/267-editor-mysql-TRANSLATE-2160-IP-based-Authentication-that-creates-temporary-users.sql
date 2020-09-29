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

#TODO do we use the default moduele here ?!!!!!!!!!!!!!!!!!!!!!!!!!!!1
#!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!1

INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('default', 'termCustomerSearch', 'frontend', 'ipBasedAuthentication');
INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('default', 'instantTranslate', 'frontend', 'ipBasedAuthentication');


INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`,`level`) 
VALUES ('runtimeOptions.authentication.ipbased.IpAddresses', '1', 'editor', 'system', '[]', '[]', '', 'list', 'List of ip addresses for ip based authentication.',4);

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`,`level`) 
VALUES ('runtimeOptions.authentication.ipbased.IpCustomerMap', '1', 'editor', 'system', '{}', '{}', '', 'map', 'Ip address to customer number map',4);

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`,`level`) 
VALUES ('runtimeOptions.authentication.ipbased.userRoles', '1', 'editor', 'system', '[]', '[]', '', 'list', 'User roles for ip base authenticated user.',4);