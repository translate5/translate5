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
UPDATE Zf_configuration SET
                 `default` = "[\"instantTranslate\",\"termCustomerSearch\"]",
                 `defaults` = "instantTranslate,termCustomerSearch",
                 `guiName` = "IP-based authentication: Assigned roles",
                 `guiGroup` = "System setup: Authentication",
                 `level` = "2",
                 `description`  = "User roles that should be assigned to users, that authenticate via IP.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.authentication.ipbased.userRoles";
UPDATE Zf_configuration SET
                 `default` = "[]",
                 `defaults` = "",
                 `guiName` = "IP-based authentication: IP list",
                 `guiGroup` = "System setup: Authentication",
                 `level` = "2",
                 `description`  = "Users coming from those IP-addresses are authenticated automatically. Please see other relevant configuration parameter for IP-based authentication. If empty, no IP addresses lead to authentication.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.authentication.ipbased.IpAddresses";
UPDATE Zf_configuration SET
                 `default` = "{}",
                 `defaults` = "",
                 `guiName` = "IP-based authentication: IP to customer number mapping",
                 `guiGroup` = "System setup: Authentication",
                 `level` = "2",
                 `description`  = "IP address to customer number map. If no IP to customer map is defined, the default customer will be assigned to the ip authenticated users.",
                 `comment` = ""
                 WHERE `name` = "runtimeOptions.authentication.ipbased.IpCustomerMap";