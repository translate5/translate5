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

INSERT INTO Zf_configuration (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) VALUES
('runtimeOptions.plugins.FrontEndMessageBus.messageBusURI', 1, 'editor', 'plugins', 'http://127.0.0.1:9057', 'http://127.0.0.1:9057', '', 'string', 'Message Bus URI, change default value according to your needs (as configured in config.php of used FrontEndMessageBus). Unix sockets are also possible, example: unix:///tmp/translate5MessageBus');

INSERT INTO Zf_configuration (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) VALUES
('runtimeOptions.plugins.FrontEndMessageBus.socketServer.schema', 1, 'editor', 'plugins', 'ws', 'ws', 'ws,wss', 'string', 'WebSocket Server default schema. In Order to use SSL, set this to wss and configure the backend accordingly. See FrontEndMessageBus/config.php.example how to enable SSL for WebSockets.');

INSERT INTO Zf_configuration (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) VALUES
('runtimeOptions.plugins.FrontEndMessageBus.socketServer.httpHost', 1, 'editor', 'plugins', '', '', '', 'string', 'WebSocket Server default HTTP host,
 if empty the current host of the application in the frontend is used. Can be configured to a fixed value here. Example: www.translate5.net');
 
INSERT INTO Zf_configuration (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) VALUES
('runtimeOptions.plugins.FrontEndMessageBus.socketServer.port', 1, 'editor', 'plugins', '9056', '9056', '', 'string', 'WebSocket Server default port, socketServer port in the FrontEndMessageBus backend config.php');

INSERT INTO Zf_configuration (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) VALUES
('runtimeOptions.plugins.FrontEndMessageBus.socketServer.route', 1, 'editor', 'plugins', '/translate5', '/translate5', '', 'string', 'WebSocket Server default route, defaults to "/translate5" and should normally not be changed. If using SSL (wss) with a ProxyPass statement, prepend the alias here. Example: "/tobedefinedbyyou/translate5" Attention: this config has nothing to do with the APPLICATION_RUNDIR in translate5!');

