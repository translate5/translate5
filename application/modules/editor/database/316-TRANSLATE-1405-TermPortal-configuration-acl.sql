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

UPDATE `Zf_configuration` SET `value` = 'finalized' WHERE `name` = 'runtimeOptions.tbx.defaultTermAttributeStatus' AND `value` = '';

INSERT IGNORE INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('editor', 'termSearch', 'editor_plugins_termportal_data', 'all');
insert ignore into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','termReviewer','auto_set_role','termSearch');
insert ignore into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','termReviewer','editor_attribute','put');
insert ignore into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','termFinalizer','auto_set_role','termSearch');
insert ignore into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','termFinalizer','editor_attribute','put');
insert ignore into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','termProposer','auto_set_role','termSearch');
insert ignore into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','termProposer','editor_term','put');
insert ignore into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','termProposer','editor_attribute','put');
insert ignore into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','termPM','auto_set_role','termSearch');
insert ignore into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','termPM','auto_set_role','termProposer');
insert ignore into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','termPM','auto_set_role','termFinalizer');
insert ignore into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','termPM','auto_set_role','termReviewer');
insert ignore into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','termPM','editor_term','delete');
insert ignore into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','termPM','editor_attribute','delete');
insert ignore into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','termPM_allClients','auto_set_role','termPM');
insert ignore into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','termPM_allClients','auto_set_role','termProposer');
insert ignore into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','termPM_allClients','auto_set_role','termSearch');
insert ignore into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','termPM_allClients','auto_set_role','termFinalizer');
insert ignore into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','termPM_allClients','auto_set_role','termReviewer');
insert ignore into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','pm','setaclrole','termReviewer');
insert ignore into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','pm','setaclrole','termFinalizer');
insert ignore into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','pm','setaclrole','termPM');
insert ignore into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','pm','setaclrole','termPM_allClients');
insert ignore into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','admin','auto_set_role','termPM');
insert ignore into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','admin','auto_set_role','termPM_allClients');
insert ignore into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','admin','auto_set_role','termProposer');
insert ignore into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','admin','auto_set_role','termSearch');
insert ignore into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','admin','auto_set_role','termFinalizer');
insert ignore into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','admin','auto_set_role','termReviewer');

UPDATE `Zf_acl_rules` SET `resource` = "editor_attribute" WHERE `role` = "termProposer" AND `resource` = "editor_termattribute" AND `right` = "post";

UPDATE `Zf_acl_rules` SET `role` = "termSearch" WHERE `role` = "termCustomerSearch";
UPDATE `Zf_acl_rules` SET `resource` = "termSearch" WHERE `resource` = "termCustomerSearch";
UPDATE `Zf_acl_rules` SET `right` = "termSearch" WHERE `right` = "termCustomerSearch";
UPDATE `Zf_users` SET `roles` = REPLACE(`roles`, "termCustomerSearch", "termSearch");

INSERT IGNORE INTO `Zf_configuration` (name, confirmed, `module`, category, `value`, `default`, defaults, type, description, level, guiName, guiGroup, comment)
VALUES ('runtimeOptions.termportal.liveSearchMinChars','1','editor','termportal','3','3','','integer','Number of typed characters to start live search in the search field','2','When to start live search','TermPortal','');

