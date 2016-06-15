-- /*
-- START LICENSE AND COPYRIGHT
-- 
--  This file is part of translate5
--  
--  Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
-- 
--  Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
-- 
--  This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
--  as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
--  included in the packaging of this file.  Please review the following information 
--  to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
--  http://www.gnu.org/licenses/agpl.html
-- 
--  There is a plugin exception available for use with this release of translate5 for
--  open source applications that are distributed under a license other than AGPL:
--  Please see Open Source License Exception for Development of Plugins for translate5
--  http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
--  folder of translate5.
--   
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
-- 			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
-- 
-- END LICENSE AND COPYRIGHT
-- */

CREATE TABLE `LEK_tmmtintegration_tmmt` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) DEFAULT NULL,
  `sourceLang` int(11) DEFAULT NULL,
  `targetLang` int(11) DEFAULT NULL,
  `color` varchar(7) DEFAULT NULL,
  `resourceId` varchar(256) DEFAULT NULL,
  `serviceType` varchar(256) DEFAULT NULL,
  `serviceName` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `LEK_tmmtintegration_taskassoc` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tmmtId` int(11) DEFAULT NULL,
  `taskGuid` varchar(38) NOT NULL,
  CONSTRAINT FOREIGN KEY (`tmmtId`) REFERENCES `LEK_tmmtintegration_tmmt` (`id`) ON DELETE CASCADE,
  CONSTRAINT FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

insert into Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES ('editor', 'pm', 'editor_plugins_tmmtintegration_tmmt', 'all');
insert into Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES ('editor', 'pm', 'editor_plugins_tmmtintegration_resource', 'all');
insert into Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES ('editor', 'pm', 'editor_plugins_tmmtintegration_taskassoc', 'all');
insert into Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES ('editor', 'pm', 'editor_plugins_tmmtintegration_query', 'all');
insert into Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES ('editor', 'editor', 'editor_plugins_tmmtintegration_tmmt', 'search');
insert into Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES ('editor', 'editor', 'editor_plugins_tmmtintegration_tmmt', 'query');

insert into Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES ('editor', 'pm', 'frontend', 'pluginMatchResourceOverview');
insert into Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES ('editor', 'pm', 'frontend', 'pluginMatchResourcesAddFilebased');
insert into Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES ('editor', 'pm', 'frontend', 'pluginMatchResourceTaskassoc');
insert into Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES ('editor', 'admin', 'frontend', 'pluginMatchResourcesAddNonFilebased');
insert into Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES ('editor', 'editor', 'frontend', 'pluginMatchResourceMatchQuery');
insert into Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES ('editor', 'editor', 'frontend', 'pluginMatchResourceSearchQuery');

 INSERT INTO Zf_configuration (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) VALUES
('runtimeOptions.plugins.TmMtIntegration.preloadedTranslationSegments', 1, 'editor', 'plugins', 3, 3, '', 'integer', 'Number of preloadet(cached) tmmt segments');