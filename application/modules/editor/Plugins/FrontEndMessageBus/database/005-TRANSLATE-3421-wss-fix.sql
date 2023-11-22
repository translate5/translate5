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
--  included in the packaging of this file.  Please review the following information2
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

DELETE FROM `Zf_configuration` WHERE `name` = "runtimeOptions.plugins.FrontEndMessageBus.socketServer.schema";

UPDATE `Zf_configuration`
SET `value` = SUBSTRING(`value`, IF (`value` LIKE '/wss%', 5, 4))
WHERE `name` = "runtimeOptions.plugins.FrontEndMessageBus.socketServer.route"
  AND `value` REGEXP '^/wss?';

UPDATE Zf_configuration
SET `description` = REPLACE(
    `description`,
    'If using SSL (wss) with a ProxyPass statement, prepend the alias here. Example: "/tobedefinedbyyou/translate5"',
    'Note: "/ws" or "/wss" is prepended automatically prior connect based on page is on http:// or https://'
)
WHERE `name` = "runtimeOptions.plugins.FrontEndMessageBus.socketServer.route";

UPDATE `Zf_configuration`
SET `value` = ""
WHERE `name` = "runtimeOptions.plugins.FrontEndMessageBus.socketServer.port"
  AND `value` IN ("80", "443");

UPDATE `Zf_configuration`
SET `default` = ""
WHERE `name` = "runtimeOptions.plugins.FrontEndMessageBus.socketServer.port";

-- Previous description was:
-- WebSocket Server default port, socketServer port in the FrontEndMessageBus backend config.php  – please see
-- https://confluence.translate5.net/display/CON/WebSocket+Server+for+FrontEndMessageBus+Plug-In for more information

-- New description is:
-- Port number to connect to WebSocket Server from the browser. If empty (default) then 80 or 443 port assumed based
-- on translate5 protocol (http:// or https://) with Apache's ProxyPass directive usage. Else if port number is explicitly
-- given - direct connection assumed with NO apache proxy usage, so value mush match socketServer port in the FrontEndMessageBus
-- backend config.php, see https://confluence.translate5.net/display/CON/WebSocket+Server+for+FrontEndMessageBus+Plug-In for more information

UPDATE `Zf_configuration`
SET `description` = "Port number to connect to WebSocket Server from the browser. If empty (default) then 80 or 443 port assumed based on translate5 protocol (http:// or https://) with Apache''s ProxyPass directive usage. Else if port number is explicitly given - direct connection assumed with NO apache proxy usage, so value mush match socketServer port in the FrontEndMessageBus backend config.php, see https://confluence.translate5.net/display/CON/WebSocket+Server+for+FrontEndMessageBus+Plug-In for more information"
WHERE `name` = "runtimeOptions.plugins.FrontEndMessageBus.socketServer.port";