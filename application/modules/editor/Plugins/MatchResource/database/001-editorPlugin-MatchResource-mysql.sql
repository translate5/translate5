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

CREATE TABLE `LEK_languageresources_tmmt` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entityVersion` int(11) NOT NULL DEFAULT 0 COMMENT 'automatic entity versioning',
  `name` varchar(30) DEFAULT NULL COMMENT 'human readable name of the service',
  `sourceLang` int(11) DEFAULT NULL COMMENT 'source language id',
  `sourceLangRfc5646` varchar(30) DEFAULT NULL COMMENT 'caches the language rfc value, since this value is used more often as the id itself',
  `targetLang` int(11) DEFAULT NULL COMMENT 'target language id',
  `targetLangRfc5646` varchar(30) DEFAULT NULL COMMENT 'caches the language rfc value, since this value is used more often as the id itself',
  `color` varchar(7) DEFAULT NULL COMMENT 'the hexadecimal colorcode',
  `resourceId` varchar(256) DEFAULT NULL COMMENT 'the id of the concrete underlying resource',
  `serviceType` varchar(256) DEFAULT NULL COMMENT 'service type class name',
  `serviceName` varchar(256) DEFAULT NULL COMMENT 'a human readable service name',
  `fileName` varchar(1024) DEFAULT NULL COMMENT 'file name for filebased TMs',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `LEK_languageresources_taskassoc` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tmmtId` int(11) DEFAULT NULL,
  `taskGuid` varchar(38) NOT NULL,
  `segmentsUpdateable` tinyint(4) NOT NULL DEFAULT ''0'',
  PRIMARY KEY (`id`),
  UNIQUE KEY `tmmtId` (`tmmtId`,`taskGuid`),
  KEY `taskGuid` (`taskGuid`),
  CONSTRAINT `LEK_languageresources_taskassoc_ibfk_1` FOREIGN KEY (`tmmtId`) REFERENCES `LEK_languageresources_tmmt` (`id`) ON DELETE CASCADE,
  CONSTRAINT `LEK_languageresources_taskassoc_ibfk_2` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

insert into Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES ('editor', 'pm', 'editor_tmmt', 'all');
insert into Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES ('editor', 'pm', 'editor_resource', 'all');
insert into Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES ('editor', 'pm', 'editor_taskassoc', 'all');
insert into Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES ('editor', 'editor', 'editor_taskassoc', 'index');
insert into Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES ('editor', 'editor', 'editor_tmmt', 'search');
insert into Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES ('editor', 'editor', 'editor_tmmt', 'query');

insert into Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES ('editor', 'pm', 'frontend', 'languageResourcesOverview');
insert into Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES ('editor', 'pm', 'frontend', 'languageResourcesAddFilebased');
insert into Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES ('editor', 'pm', 'frontend', 'languageResourcesTaskassoc');
insert into Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES ('editor', 'admin', 'frontend', 'languageResourcesAddNonFilebased');
insert into Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES ('editor', 'editor', 'frontend', 'languageResourcesMatchQuery');
insert into Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES ('editor', 'editor', 'frontend', 'languageResourcesSearchQuery');

INSERT INTO Zf_configuration (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) VALUES
('runtimeOptions.LanguageResources.preloadedTranslationSegments', 1, 'editor', 'editor', 1, 1, '', 'integer', 'Number of segments for which matches are preloaded (cached)');

INSERT INTO Zf_configuration (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) VALUES
('runtimeOptions.LanguageResources.moses.server', 1, 'editor', 'editor', '[]', '[]', '', 'list', 'List of available Moses Server, example: ["http://www.translate5.net:8124/RPC2"]');

INSERT INTO Zf_configuration (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) VALUES
('runtimeOptions.LanguageResources.moses.matchrate', 1, 'editor', 'editor', '70', '70', '', 'integer', 'Moses MT penalty value, used as default matchrate since in MT no matchrate is available');

-- trigger for tmmt versioning

 DELIMITER |
  CREATE TRIGGER LEK_languageresources_tmmt_versioning BEFORE UPDATE ON LEK_languageresources_tmmt
      FOR EACH ROW 
        IF OLD.entityVersion = NEW.entityVersion THEN 
          SET NEW.entityVersion = OLD.entityVersion + 1;
        ELSE 
          CALL raise_version_conflict; 
        END IF|
  DELIMITER ;
