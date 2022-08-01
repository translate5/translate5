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

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `typeClass`, `description`, `level`, `guiName`, `guiGroup`)
VALUES ('runtimeOptions.plugins.Okapi.server', '1', 'editor', 'plugins', '', '', '', 'map', 'editor_Plugins_Okapi_DbConfig_OkapiConfigType', 'Available okapi instances with unique names. Do not change the name after the instance is assigned to a task.', '2', 'Okapi longhorn available instances', 'System setup: General');


INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`, `guiName`, `guiGroup`)
VALUES ('runtimeOptions.plugins.Okapi.serverUsed', '1', 'editor', 'plugins', '', '', '', 'string', 'Okapi server used for the a task. All available values are automatically generated out of the runtimeOptions.plugins.Okapi.server config', '8', 'Okapi longhorn server used for a task', 'System setup: General');


# Update the available okapi servers from the okapi api url config
UPDATE `Zf_configuration` as `m`, (SELECT `value` FROM `Zf_configuration` WHERE `name` = 'runtimeOptions.plugins.Okapi.api.url') as `p`
SET `m`.`value` = CONCAT('{"used-on-',YEAR(CURRENT_DATE()),'-',MONTH(CURRENT_DATE()),'":"',(`p`.`value`),'"}'),
`m`.`default` = CONCAT('{"used-on-',YEAR(CURRENT_DATE()),'-',MONTH(CURRENT_DATE()),'":"',(`p`.`value`),'"}')
WHERE (`m`.`name` = 'runtimeOptions.plugins.Okapi.server');

# Update the server used value with the same value as the okapi server
UPDATE `Zf_configuration`
SET `value` = CONCAT('used-on-',YEAR(CURRENT_DATE()),'-',MONTH(CURRENT_DATE())),
    `defaults` = CONCAT('used-on-',YEAR(CURRENT_DATE()),'-',MONTH(CURRENT_DATE())),
    `default` = CONCAT('used-on-',YEAR(CURRENT_DATE()),'-',MONTH(CURRENT_DATE()))
WHERE (`name` = 'runtimeOptions.plugins.Okapi.serverUsed');