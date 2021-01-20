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

UPDATE Zf_configuration SET
                `default` = "/translate5",
                `defaults` = "",
                `guiName` = "Websockets server default route",
                `guiGroup` = "System setup: General",
                `level` = "2",
                `description`  = "WebSocket Server default route, defaults to \"/translate5\" and should normally not be changed. If using SSL (wss) with a ProxyPass statement, prepend the alias here. Example: \"/tobedefinedbyyou/translate5\" Attention: this config has nothing to do with the APPLICATION_RUNDIR in translate5!",
                `comment` = ""
                WHERE `name` = "runtimeOptions.plugins.FrontEndMessageBus.socketServer.route";
UPDATE Zf_configuration SET
                `default` = "",
                `defaults` = "",
                `guiName` = "Websockets server host",
                `guiGroup` = "System setup: General",
                `level` = "2",
                `description`  = "WebSocket Server default HTTP host, if empty the current host of the application in the frontend is used. Can be configured to a fixed value here. Example: www.translate5.net – please see https://confluence.translate5.net/display/CON/WebSocket+Server+for+FrontEndMessageBus+Plug-In for more information",
                `comment` = ""
                WHERE `name` = "runtimeOptions.plugins.FrontEndMessageBus.socketServer.httpHost";
UPDATE Zf_configuration SET
                `default` = "9056",
                `defaults` = "",
                `guiName` = "Websockets server port",
                `guiGroup` = "System setup: General",
                `level` = "2",
                `description`  = "WebSocket Server default port, socketServer port in the FrontEndMessageBus backend config.php  – please see https://confluence.translate5.net/display/CON/WebSocket+Server+for+FrontEndMessageBus+Plug-In for more information",
                `comment` = ""
                WHERE `name` = "runtimeOptions.plugins.FrontEndMessageBus.socketServer.port";
UPDATE Zf_configuration SET
                `default` = "ws",
                `defaults` = "ws,wss",
                `guiName` = "Websockets server protocol",
                `guiGroup` = "System setup: General",
                `level` = "2",
                `description`  = "WebSocket Server default schema. In Order to use SSL, set this to wss instead of ws and configure the backend accordingly. See FrontEndMessageBus/config.php.example how to enable SSL for WebSockets.",
                `comment` = ""
                WHERE `name` = "runtimeOptions.plugins.FrontEndMessageBus.socketServer.schema";
UPDATE Zf_configuration SET
                `default` = "http://127.0.0.1:9057",
                `defaults` = "",
                `guiName` = "Websockets server URL",
                `guiGroup` = "System setup: General",
                `level` = "2",
                `description`  = "Message Bus URI, change default value according to your needs (as configured in config.php of used FrontEndMessageBus). Unix sockets are also possible, example: unix:///tmp/translate5MessageBus – please see https://confluence.translate5.net/display/CON/WebSocket+Server+for+FrontEndMessageBus+Plug-In for more information",
                `comment` = ""
                WHERE `name` = "runtimeOptions.plugins.FrontEndMessageBus.messageBusURI";
