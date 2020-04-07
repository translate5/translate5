
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


ALTER TABLE `Zf_configuration` 
ADD COLUMN `level` INT(11) NULL DEFAULT 1 COMMENT 'Configuration level' AFTER `description`;

DELETE FROM `Zf_configuration` WHERE `name`='runtimeOptions.frontend.defaultState';

SET foreign_key_checks = 0;

CREATE TABLE `LEK_user_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userGuid` varchar(38) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `value` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`,`userGuid`),
  KEY `userGuid` (`userGuid`),
  CONSTRAINT `LEK_user_config_ibfk_1` FOREIGN KEY (`userGuid`) REFERENCES `Zf_users` (`userGuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

SET foreign_key_checks = 1;

INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('editor', 'editor', 'editor_config', 'all');
INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('editor', 'pm', 'editor_config', 'all');

INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('editor', 'admin', 'applicationconfig', 'system');
INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('editor', 'editor', 'applicationconfig', 'user');


INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`) 
VALUES 
('runtimeOptions.frontend.defaultState.helpWindow.customeroverview', '1', 'editor', 'system', '{"doNotShowAgain":false}', '', '', 'map', 'Help window default state configuration for the customeroverview panel. When doNotShowAgain is set to false, the window will appear automaticly for customeroverview panel. When setting this config to true or leave the field value as empty, the window will not apear automaticly.', '1');

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`) 
VALUES 
('runtimeOptions.frontend.defaultState.helpWindow.taskoverview', '1', 'editor', 'system', '{"doNotShowAgain":false}', '', '', 'map', 'Help window default state configuration for the taskoverview panel. When doNotShowAgain is set to false, the window will appear automaticly for taskoverview panel. When setting this config to true or leave the field value as empty, the window will not apear automaticly.', '1');

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`) 
VALUES 
('runtimeOptions.frontend.defaultState.helpWindow.useroverview', '1', 'editor', 'system', '{"doNotShowAgain":false}', '', '', 'map', 'Help window default state configuration for the useroverview panel. When doNotShowAgain is set to false, the window will appear automaticly for useroverview panel. When setting this config to true or leave the field value as empty, the window will not apear automaticly.', '1');

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`) 
VALUES 
('runtimeOptions.frontend.defaultState.helpWindow.editor', '1', 'editor', 'system', '{"doNotShowAgain":false}', '', '', 'map', 'Help window default state configuration for the editor panel. When doNotShowAgain is set to false, the window will appear automaticly for editor panel. When setting this config to true or leave the field value as empty, the window will not apear automaticly.', '1');

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`) 
VALUES 
('runtimeOptions.frontend.defaultState.helpWindow.languageresource', '1', 'editor', 'system', '{"doNotShowAgain":false}', '', '', 'map', 'Help window default state configuration for the languageresource panel. When doNotShowAgain is set to false, the window will appear automaticly for languageresource panel. When setting this config to true or leave the field value as empty, the window will not apear automaticly.', '1');


INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`) 
VALUES 
('runtimeOptions.frontend.defaultState.adminTaskGrid', '1', 'editor', 'system', '', '', '', 'map', 'Task grid default state configuration. When this config is empty, the task grid state will not be saved or applied. For how to config this value please visit this page: https://confluence.translate5.net/display/CON/Configure+grids+and+window+state ', '1')
ON DUPLICATE KEY UPDATE `name`='runtimeOptions.frontend.defaultState.adminTaskGrid';


INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`) 
VALUES 
('runtimeOptions.frontend.defaultState.adminUserGrid', '1', 'editor', 'system', '', '', '', 'map', 'User grid default state configuration. When this config is empty, the task grid state will not be saved or applied. For how to config this value please visit this page: https://confluence.translate5.net/display/CON/Configure+grids+and+window+state', '1') 
ON DUPLICATE KEY UPDATE `name`='runtimeOptions.frontend.defaultState.adminUserGrid';


