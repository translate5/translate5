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

insert into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','termReviewer','auto_set_role','termSearch');
insert into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','termReviewer','editor_attribute','put');
insert into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','termFinalizer','auto_set_role','termSearch');
insert into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','termFinalizer','editor_attribute','put');
insert into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','termProposer','auto_set_role','termSearch');
insert into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','termProposer','editor_term','put');
insert into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','termProposer','editor_attribute','put');
insert into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','termPM','auto_set_role','termSearch');
insert into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','termPM','auto_set_role','termProposer');
insert into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','termPM','editor_term','delete');
insert into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','termPM','editor_attribute','delete');
insert into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','termPM_allClients','auto_set_role','termPM');
insert into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','termPM_allClients','auto_set_role','termProposer');
insert into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','termPM_allClients','auto_set_role','termSearch');
insert into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','pm','setaclrole','termReviewer');
insert into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','pm','setaclrole','termFinalizer');
insert into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','pm','setaclrole','termPM');
insert into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','pm','setaclrole','termPM_allClients');
insert into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','admin','auto_set_role','termPM');
insert into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values('editor','admin','auto_set_role','termPM_allClients');
UPDATE `Zf_acl_rules` SET `resource` = "editor_attribute" WHERE `role` = "termProposer" AND `resource` = "editor_termattribute" AND `right` = "post";