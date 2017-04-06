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

CREATE TABLE `LEK_matchresource_tmmt` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entityVersion` int(11) NOT NULL DEFAULT 0 COMMENT 'automatic entity versioning',
  `name` varchar(30) DEFAULT NULL COMMENT 'human readable name of the service',
  `sourceLang` int(11) DEFAULT NULL COMMENT 'source language id',
  `targetLang` int(11) DEFAULT NULL COMMENT 'target language id',
  `color` varchar(7) DEFAULT NULL COMMENT 'the hexadecimal colorcode',
  `resourceId` varchar(256) DEFAULT NULL COMMENT 'the id of the concrete underlying resource',
  `serviceType` varchar(256) DEFAULT NULL COMMENT 'service type class name',
  `serviceName` varchar(256) DEFAULT NULL COMMENT 'a human readable service name',
  `fileName` varchar(1024) DEFAULT NULL COMMENT 'file name for filebased TMs',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `LEK_matchresource_taskassoc` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tmmtId` int(11) DEFAULT NULL,
  `taskGuid` varchar(38) NOT NULL,
  CONSTRAINT FOREIGN KEY (`tmmtId`) REFERENCES `LEK_matchresource_tmmt` (`id`) ON DELETE CASCADE,
  CONSTRAINT FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE,
  UNIQUE KEY (`tmmtId`, `taskGuid`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

insert into Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES ('editor', 'pm', 'editor_plugins_matchresource_tmmt', 'all');
insert into Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES ('editor', 'pm', 'editor_plugins_matchresource_resource', 'all');
insert into Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES ('editor', 'pm', 'editor_plugins_matchresource_taskassoc', 'all');
insert into Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES ('editor', 'editor', 'editor_plugins_matchresource_taskassoc', 'index');
insert into Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES ('editor', 'editor', 'editor_plugins_matchresource_tmmt', 'search');
insert into Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES ('editor', 'editor', 'editor_plugins_matchresource_tmmt', 'query');

insert into Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES ('editor', 'pm', 'frontend', 'pluginMatchResourceOverview');
insert into Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES ('editor', 'pm', 'frontend', 'pluginMatchResourcesAddFilebased');
insert into Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES ('editor', 'pm', 'frontend', 'pluginMatchResourceTaskassoc');
insert into Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES ('editor', 'admin', 'frontend', 'pluginMatchResourcesAddNonFilebased');
insert into Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES ('editor', 'editor', 'frontend', 'pluginMatchResourceMatchQuery');
insert into Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES ('editor', 'editor', 'frontend', 'pluginMatchResourceSearchQuery');

INSERT INTO Zf_configuration (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) VALUES
('runtimeOptions.plugins.MatchResource.preloadedTranslationSegments', 1, 'editor', 'plugins', 3, 3, '', 'integer', 'Number of segments for which matches are preloaded (cached)');

INSERT INTO Zf_configuration (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) VALUES
('runtimeOptions.plugins.MatchResource.moses.server', 1, 'editor', 'plugins', '[]', '[]', '', 'list', 'List of available Moses Server, example: ["http://www.translate5.net:8124/RPC2"]');

INSERT INTO Zf_configuration (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) VALUES
('runtimeOptions.plugins.MatchResource.moses.matchrate', 1, 'editor', 'plugins', '70', '70', '', 'integer', 'Moses MT penalty value, used as default matchrate since in MT no matchrate is available');

UPDATE `Zf_configuration` SET `value` = REPLACE(`value`, ']', ',"editor/plugins/resources/matchResource/plugin.css"]') 
WHERE `name` = 'runtimeOptions.publicAdditions.css' AND `value` != '[]';

UPDATE `Zf_configuration` SET `value` = '["editor/plugins/resources/matchResource/plugin.css"]' 
WHERE `name` = 'runtimeOptions.publicAdditions.css' AND `value` = '[]';


-- trigger for tmmt versioning

 DELIMITER |
  CREATE TRIGGER LEK_matchresource_tmmt_versioning BEFORE UPDATE ON LEK_matchresource_tmmt
      FOR EACH ROW 
        IF OLD.entityVersion = NEW.entityVersion THEN 
          SET NEW.entityVersion = OLD.entityVersion + 1;
        ELSE 
          CALL raise_version_conflict; 
        END IF|
  DELIMITER ;